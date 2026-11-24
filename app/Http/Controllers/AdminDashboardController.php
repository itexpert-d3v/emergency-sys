<?php

namespace App\Http\Controllers;

use App\Models\BannedIp;
use App\Models\BannedPhone;
use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Services\Security\ProgressiveRateLimitState;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $selectedMonth = $request->query('month');

        if ($selectedMonth) {
            $startDate = Carbon::createFromFormat('Y-m', $selectedMonth)->startOfMonth();
            $endDate = $startDate->copy()->endOfMonth();

            $dailyCounts = IncidentReport::query()
                ->whereBetween('submitted_at', [$startDate, $endDate])
                ->selectRaw('DATE(submitted_at) as report_date, COUNT(*) as total')
                ->groupBy('report_date')
                ->pluck('total', 'report_date');

            $reportTrend = collect(range(1, $endDate->daysInMonth))->map(function (int $day) use ($startDate, $dailyCounts): array {
                $date = $startDate->copy()->day($day);
                $key = $date->toDateString();

                return ['label' => $date->format('j'), 'date' => $key, 'count' => (int) ($dailyCounts[$key] ?? 0)];
            });
        } else {
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

                return ['label' => $date->format('D'), 'date' => $key, 'count' => (int) ($dailyCounts[$key] ?? 0)];
            });
        }

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
            ->limit(10)
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
            'selectedMonth' => $selectedMonth,
        ]);
    }

    public function queue(Request $request): View
    {
        $queueCounts = [
            'all' => IncidentReport::query()->where('status', '!=', 'rejected')->count(),
            'submitted' => IncidentReport::query()->where('status', 'submitted')->count(),
            'in_progress' => IncidentReport::query()->where('status', 'in_progress')->count(),
            'completed' => IncidentReport::query()->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $reports = IncidentReport::query()
            ->with(['category'])
            ->where('status', '!=', 'rejected')
            ->when($request->query('status') === 'completed', fn ($query) => $query->whereIn('status', ['resolved', 'closed']))
            ->when($request->filled('status') && $request->query('status') !== 'completed', fn ($query) => $query->where('status', $request->query('status')))
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('admin.queue', [
            'reports' => $reports,
            'status' => $request->status,
            'queueCounts' => $queueCounts,
            'categories' => IncidentCategory::query()->active()->get(),
            'bannedIpAddresses' => BannedIp::query()->active()->pluck('ip_address')->all(),
            'bannedPhoneIdsByNumber' => BannedPhone::query()->pluck('id', 'phone_number')->all(),
        ]);
    }

    public function acceptReport(Request $request, IncidentReport $report): JsonResponse|RedirectResponse
    {
        $accepted = IncidentReport::query()
            ->whereKey($report->id)
            ->where('status', '!=', 'rejected')
            ->whereNull('accepted_at')
            ->update(['accepted_at' => now(), 'updated_at' => now()]) === 1;

        $result = [
            'accepted' => $accepted,
            'id' => $report->id,
            'redirect_url' => route('admin.dashboard.queue', ['expand' => $report->id]),
        ];

        if (! $request->expectsJson()) {
            return redirect()->to($result['redirect_url'])->with('status', $accepted
                ? 'Report accepted.'
                : 'This report was already accepted or rejected.');
        }

        return response()->json($result);
    }

    public function banIp(Request $request, ProgressiveRateLimitState $rateLimitState): RedirectResponse
    {
        $data = $request->validate(['ip_address' => ['required', 'ip']]);
        $ipAddress = BannedIp::normalize($data['ip_address']);

        $ban = BannedIp::query()->updateOrCreate(
            ['ip_address' => $ipAddress],
            ['expires_at' => now()->addHours(24)]
        );
        $rateLimitState->clearIp($ipAddress);
        $rateLimitKey = BannedIp::limiterKey($ipAddress);
        RateLimiter::clear($rateLimitKey);
        RateLimiter::hit($rateLimitKey, now()->diffInSeconds($ban->expires_at));

        return $this->returnToModerationOrQueue($request, 'IP address banned.');
    }

    public function unbanIp(BannedIp $bannedIp, ProgressiveRateLimitState $rateLimitState): RedirectResponse
    {
        $rateLimitState->clearIp($bannedIp->ip_address);
        RateLimiter::clear(BannedIp::limiterKey($bannedIp->ip_address));
        $bannedIp->delete();

        return redirect()->route('admin.moderation.index')->with('status', 'IP address unbanned.');
    }

    public function unbanReporterIp(IncidentReport $report, Request $request, ProgressiveRateLimitState $rateLimitState): RedirectResponse
    {
        if ($report->reporter_ip) {
            $ipAddress = BannedIp::normalize($report->reporter_ip);
            $rateLimitState->clearIp($ipAddress);
            RateLimiter::clear(BannedIp::limiterKey($ipAddress));
            BannedIp::query()->where('ip_address', $ipAddress)->delete();
        }

        return $this->returnToModerationOrQueue($request, 'IP address unbanned.');
    }

    public function banPhone(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone_number' => ['required', 'string', 'regex:/^\+63[0-9]{10,12}$/']]);
        BannedPhone::query()->firstOrCreate([
            'phone_number' => BannedPhone::normalize($data['phone_number']),
        ]);

        return $this->returnToModerationOrQueue($request, 'Phone number banned.');
    }

    public function unbanPhone(BannedPhone $bannedPhone, Request $request): RedirectResponse
    {
        $bannedPhone->delete();

        return $this->returnToModerationOrQueue($request, 'Phone number unbanned.');
    }

    private function returnToModerationOrQueue(Request $request, string $message): RedirectResponse
    {
        $reportId = $request->input('report_id');

        return $reportId
            ? redirect()->route('admin.dashboard.queue', ['expand' => $reportId])->with('status', $message)
            : redirect()->route('admin.moderation.index')->with('status', $message);
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

    /** Returns active reports, pending acceptance details, and recent conversation messages. */
    public function liveMap(): JsonResponse
    {
        // 2. ACTIVE REPORTS
        $reports = IncidentReport::query()
            ->with('category')
            ->whereIn('status', ['submitted', 'in_progress', 'queued'])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($report) {
                return [
                    'id' => $report->id,
                    'reference_code' => $report->reference_code,
                    'category' => $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident',
                    'priority' => $report->priority ?? 'low',
                    'status' => $report->status,
                    'location' => $report->location_text,
                    'description' => $report->description,
                    'reporter_name' => $report->reporter_name,
                    'contact_number' => $report->contact_number,
                    'lat' => $report->latitude,
                    'lng' => $report->longitude,
                    'time' => $report->submitted_at?->diffForHumans() ?? $report->created_at?->diffForHumans() ?? 'Unknown time',
                ];
            });

        $pendingReports = IncidentReport::query()
            ->with('category')
            ->whereNull('accepted_at')
            ->where('status', '!=', 'rejected')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($report) => [
                'id' => $report->id,
                'reference_code' => $report->reference_code,
                'category' => $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Unclassified report',
                'priority' => $report->priority ?? 'low',
                'status' => $report->status,
                'location' => $report->location_text,
                'description' => $report->description,
                'reporter_name' => $report->reporter_name,
                'contact_number' => $report->contact_number,
                'submitted_at' => $report->submitted_at?->toISOString(),
                'lat' => $report->latitude,
                'lng' => $report->longitude,
            ])
            ->values();

        // 4. RETURN WITH NO-CACHE HEADERS (Prevents hard refresh issues)
        return response()->json([
            'reports' => $reports,
            'pending_reports' => $pendingReports,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}
