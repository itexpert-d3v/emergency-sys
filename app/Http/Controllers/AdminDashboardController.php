<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        if (! Auth::check() || (Auth::user()->role ?? 'staff') !== 'admin') {
            abort(403, 'Admin access required.');
        }

        $today = now()->startOfDay();
        $trendStart = $today->copy()->subDays(6);
        $dailyCounts = IncidentReport::query()
            ->where('submitted_at', '>=', $trendStart)
            ->selectRaw('DATE(submitted_at) as report_date, COUNT(*) as total')
            ->groupBy('report_date')
            ->pluck('total', 'report_date');
        $reportTrend = collect(range(6, 0))->map(function (int $daysAgo) use ($today, $dailyCounts): array {
            $date = $today->copy()->subDays($daysAgo);
            $key = $date->toDateString();

            return [
                'label' => $date->format('D'),
                'date' => $key,
                'count' => (int) ($dailyCounts[$key] ?? 0),
            ];
        });
        $trendMax = max(1, (int) $reportTrend->max('count'));
        $reportTrend = $reportTrend->map(function (array $day) use ($trendMax): array {
            $day['height'] = $day['count'] === 0 ? 4 : max(12, (int) round($day['count'] / $trendMax * 100));

            return $day;
        });
        $statusCounts = IncidentReport::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $reports = IncidentReport::query()
            ->with(['category'])
            ->orderByDesc('created_at')
            ->limit(12)
            ->get();

        return view('admin.dashboard', [
            'reports' => $reports,
            'summary' => [
                'total' => IncidentReport::query()->count(),
                'open' => IncidentReport::query()->whereIn('status', ['submitted', 'in_progress', 'queued'])->count(),
                'resolved' => IncidentReport::query()->where('status', 'resolved')->count(),
                'critical' => IncidentReport::query()->where('priority', 'critical')->count(),
            ],
            'reportTrend' => $reportTrend,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function queue(Request $request): View
    {
        if (! Auth::check() || (Auth::user()->role ?? 'staff') !== 'admin') {
            abort(403, 'Admin access required.');
        }

        $reports = IncidentReport::query()
            ->with(['category'])
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.queue', [
            'reports' => $reports,
            'status' => $request->status,
        ]);
    }

    public function dispatchResponder(Request $request, IncidentReport $report): RedirectResponse
    {
        $data = $request->validate([
            'responder' => ['required', 'string', 'in:ambulance,response_group'],
            'dispatch_confirmed' => ['accepted'],
        ]);

        if (in_array($report->status, ['resolved', 'closed'], true)) {
            return redirect()->route('admin.dashboard.queue')
                ->withErrors(['responder' => 'A responder cannot be marked en route for a resolved or closed report.']);
        }

        $notice = $data['responder'] === 'ambulance'
            ? 'MDRRMO update: An ambulance is on the way.'
            : 'MDRRMO update: A response group is on the way.';

        DB::transaction(function () use ($report, $notice, $request): void {
            if ($report->status !== 'in_progress') {
                $report->status = 'in_progress';
                $report->save();
                $report->statusEvents()->create([
                    'status' => 'in_progress',
                    'actor_user_id' => $request->user()?->id,
                ]);
            }

            $alreadyNotified = $report->messages()
                ->where('sender_type', 'staff')
                ->where('body', $notice)
                ->exists();

            if (! $alreadyNotified) {
                $report->messages()->create([
                    'sender_type' => 'staff',
                    'body' => $notice,
                    'staff_actor_id' => $request->user()?->id,
                    'closed_thread' => false,
                ]);
            }
        });

        return redirect()->route('admin.dashboard.queue')
            ->with('status', 'Dispatch confirmation recorded and shared with the reporter.');
    }
}
