<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\ReportMessage;
use App\Models\ReportTrackingToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportMessageController extends Controller
{
    public function reporterIndex(string $token): JsonResponse
    {
        $report = $this->resolveReportForToken($token);

        if (! $report) {
            return response()->json(['error' => 'This tracking link is invalid or has expired.'], 404);
        }

        return response()->json([
            'report_id' => $report->id,
            'thread_closed' => (bool) $report->thread_closed,
            'messages' => $report->messages()->orderBy('created_at')->get()->map(function (ReportMessage $message) {
                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'body' => $message->body,
                    'created_at' => $message->created_at?->toISOString(),
                ];
            })->all(),
        ]);
    }

    public function reporterStore(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
        ]);

        $report = $this->resolveReportForToken($token);
        if (! $report) {
            return response()->json(['error' => 'This tracking link is invalid or has expired.'], 404);
        }

        if ((bool) $report->thread_closed) {
            return response()->json(['error' => 'This thread is closed.'], 403);
        }

        $message = $report->messages()->create([
            'sender_type' => 'reporter',
            'body' => strip_tags($data['body']),
            'staff_actor_id' => null,
            'closed_thread' => false,
        ]);

        return response()->json([
            'id' => $message->id,
            'report_id' => $message->report_id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'created_at' => $message->created_at?->toISOString(),
        ]);
    }

    public function staffIndex(IncidentReport $report): JsonResponse
    {
        return response()->json([
            'report_id' => $report->id,
            'thread_closed' => (bool) $report->thread_closed,
            'messages' => $report->messages()->orderBy('created_at')->get()->map(function (ReportMessage $message) {
                return [
                    'id' => $message->id,
                    'sender_type' => $message->sender_type,
                    'body' => $message->body,
                    'created_at' => $message->created_at?->toISOString(),
                    'staff_actor_id' => $message->staff_actor_id,
                ];
            })->all(),
        ]);
    }

    public function staffStore(Request $request, IncidentReport $report): JsonResponse
    {
        // A checkbox sends "on" or nothing; normalize to a real boolean first.
        $request->merge(['close_thread' => $request->boolean('close_thread')]);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:500'],
            'close_thread' => ['sometimes', 'boolean'],
        ]);

        if ((bool) $report->thread_closed) {
            return response()->json(['error' => 'This thread is closed.'], 403);
        }

        $closeThread = (bool) ($data['close_thread'] ?? false);

        $message = $report->messages()->create([
            'sender_type' => 'staff',
            'body' => strip_tags($data['body']),
            'staff_actor_id' => $request->user()?->id,
            'closed_thread' => $closeThread,
        ]);

        if ($closeThread) {
            $report->thread_closed = true;
            $report->save();
        }

        return response()->json([
            'id' => $message->id,
            'report_id' => $message->report_id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'created_at' => $message->created_at?->toISOString(),
            'thread_closed' => (bool) $report->thread_closed,
        ]);
    }

    protected function resolveReportForToken(string $token): ?IncidentReport
    {
        $trackingToken = ReportTrackingToken::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (! $trackingToken) {
            return null;
        }

        if ($trackingToken->revoked_at || ($trackingToken->expires_at && $trackingToken->expires_at->isPast())) {
            return null;
        }

        return $trackingToken->report()->first();
    }
}