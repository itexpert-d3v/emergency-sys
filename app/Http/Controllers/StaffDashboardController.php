<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\ReportMessage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StaffDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $queueCounts = [
            'all' => IncidentReport::query()->where('status', '!=', 'rejected')->count(),
            'submitted' => IncidentReport::query()->where('status', 'submitted')->count(),
            'in_progress' => IncidentReport::query()->where('status', 'in_progress')->count(),
            'completed' => IncidentReport::query()->whereIn('status', ['resolved', 'closed'])->count(),
        ];

        $reports = IncidentReport::query()
            ->where('status', '!=', 'rejected')
            ->when($request->query('status') === 'completed', fn ($query) => $query->whereIn('status', ['resolved', 'closed']))
            ->when($request->filled('status') && $request->query('status') !== 'completed', fn ($query) => $query->where('status', $request->query('status')))
            ->orderByDesc('submitted_at')
            ->get();

        return view('staff.dashboard', [
            'reports' => $reports,
            'status' => $request->query('status', ''),
            'queueCounts' => $queueCounts,
        ]);
    }

    public function poll(): JsonResponse
    {
        $freshReports = IncidentReport::query()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        $newMessages = ReportMessage::query()
            ->where('created_at', '>=', now()->subMinutes(5))
            ->count();

        return response()->json([
            'new_reports' => $freshReports,
            'new_messages' => $newMessages,
            'updated_at' => now()->toISOString(),
        ]);
    }

    public function triage(Request $request, IncidentReport $report): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['nullable', 'string', 'in:submitted,in_progress,resolved,closed'],
            'priority' => ['nullable', 'string', 'in:low,medium,high,critical'],
            'category_id' => [Rule::prohibitedIf(fn () => $request->user()?->role !== 'admin'), 'nullable', 'integer', 'exists:incident_categories,id,active,1'],
            'remarks' => [Rule::prohibitedIf(fn () => $request->user()?->role !== 'admin'), 'nullable', 'string', 'max:2000'],
            'return_to' => ['nullable', 'string', 'in:staff,admin'],
        ]);

        DB::transaction(function () use ($report, $data, $request): void {
            $statusChanged = array_key_exists('status', $data)
                && $data['status'] !== null
                && $data['status'] !== $report->status;

            if (array_key_exists('status', $data) && $data['status'] !== null) {
                $report->status = $data['status'];
            }

            if (array_key_exists('priority', $data) && $data['priority'] !== null) {
                $report->priority = $data['priority'];
            }

            if (array_key_exists('category_id', $data)) {
                $report->category_id = $data['category_id'];
            }

            if (array_key_exists('remarks', $data)) {
                $report->remarks = $data['remarks'];
            }

            $report->save();

            if ($statusChanged) {
                $report->statusEvents()->create([
                    'status' => $report->status,
                    'actor_user_id' => $request->user()?->id,
                ]);
            }
        });

        $destination = ($data['return_to'] ?? 'staff') === 'admin' && $request->user()?->role === 'admin'
            ? 'admin.dashboard.queue'
            : 'operations.queue';

        return redirect()->route($destination)->with('status', 'Report updated successfully.');
    }
}
