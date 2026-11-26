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
            'body' => ['required', 'string', 'max:50'],
        ]);

        $report = $this->resolveReportForToken($token);
        if (! $report) {
            return response()->json(['error' => 'This tracking link is invalid or has expired.'], 404);
        }

        if ((bool) $report->thread_closed) {
            return response()->json(['error' => 'This thread is closed.'], 403);
        }

        $clean = $this->sanitizeMessage($data['body']);
        if ($clean === '') {
            return response()->json([
                'error' => 'Please write a message before sending.',
                'errors' => ['body' => ['Please write a message before sending.']],
            ], 422);
        }

        $message = $report->messages()->create([
            'sender_type' => 'reporter',
            'body' => $clean,
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
            'body' => ['required', 'string', 'max:50'],
            'close_thread' => ['sometimes', 'boolean'],
        ]);

        if ((bool) $report->thread_closed) {
            return response()->json(['error' => 'This thread is closed.'], 403);
        }

        $clean = $this->sanitizeMessage($data['body']);
        if ($clean === '') {
            return response()->json([
                'error' => 'Please write a message before sending.',
                'errors' => ['body' => ['Please write a message before sending.']],
            ], 422);
        }

        $closeThread = (bool) ($data['close_thread'] ?? false);

        $message = $report->messages()->create([
            'sender_type' => 'staff',
            'body' => $clean,
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

    /**
     * Strip HTML, control characters, and excess whitespace, then hard-cap
     * the result at 50 characters. Returns an empty string if nothing usable
     * remains (caller should reject with a 422).
     */
    protected function sanitizeMessage(string $raw): string
    {
        // 1. Strip HTML tags (including any embedded script/style content).
        $text = strip_tags($raw);

        // 2. Remove control chars and zero-width characters (incl. ZWSP, ZWJ, BOM).
        $text = preg_replace('/[\x00-\x1F\x7F\x{200B}-\x{200F}\x{202A}-\x{202E}\x{FEFF}]/u', '', $text);

        // 3. Collapse runs of whitespace into a single space.
        $text = preg_replace('/\s+/u', ' ', $text);

        // 4. Trim ends.
        $text = trim($text);

        // 5. Hard cap at 50 characters (multibyte-safe).
        if (function_exists('mb_substr')) {
            $text = mb_substr($text, 0, 50, 'UTF-8');
        } else {
            $text = substr($text, 0, 50);
        }

        return $text;
    }
}