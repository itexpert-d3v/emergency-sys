<?php

namespace App\Http\Controllers;

use App\Models\BannedIp;
use App\Models\BannedPhone;
use App\Models\IncidentReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminModerationController extends Controller
{
    public function index(): View
    {
        return view('admin.moderation', [
            'bannedIps' => BannedIp::query()->active()->orderBy('ip_address')->get(),
            'bannedPhones' => BannedPhone::query()->orderBy('phone_number')->get(),
            'deletedReports' => IncidentReport::onlyTrashed()
                ->with('category')
                ->orderByDesc('deleted_at')
                ->get(),
            'rejectedReports' => IncidentReport::query()
                ->where('status', 'rejected')
                ->with('category')
                ->orderByDesc('rejected_at')
                ->get(),
        ]);
    }

    public function reject(Request $request, IncidentReport $report): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($report, $data, $request): void {
            $report->status = 'rejected';
            $report->rejected_at = now();
            $report->rejection_reason = trim($data['rejection_reason']);
            $report->save();
            $report->statusEvents()->create([
                'status' => 'rejected',
                'actor_user_id' => $request->user()?->id,
            ]);
        });

        return redirect()->route('admin.moderation.index')->with('status', 'Report rejected and moved to moderation.');
    }

    public function reopen(string $reportId, Request $request): RedirectResponse
    {
        $report = IncidentReport::query()->whereKey($reportId)->where('status', 'rejected')->firstOrFail();

        DB::transaction(function () use ($report, $request): void {
            $report->status = 'submitted';
            $report->rejected_at = null;
            $report->rejection_reason = null;
            $report->save();
            $report->statusEvents()->create([
                'status' => 'submitted',
                'actor_user_id' => $request->user()?->id,
            ]);
        });

        return redirect()->route('admin.moderation.index')->with('status', 'Rejected report reopened in the incident queue.');
    }

    public function softDelete(IncidentReport $report): RedirectResponse
    {
        $report->delete();

        return redirect()->route('admin.moderation.index')
            ->with('status', 'Report moved to deleted reports. It can be restored from this page.');
    }

    public function restore(string $reportId): RedirectResponse
    {
        $report = IncidentReport::withTrashed()->findOrFail($reportId);
        $report->restore();

        return redirect()->route('admin.moderation.index')
            ->with('status', 'Report restored to the incident queue.');
    }
}