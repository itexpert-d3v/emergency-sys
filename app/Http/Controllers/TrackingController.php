<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use App\Models\ReportMessage;
use App\Models\ReportTrackingToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class TrackingController extends Controller
{
    public function show(Request $request): Response
    {
        return $this->resolve(
            (string) $request->query('reference', ''),
            (string) $request->query('token', '')
        );
    }

    public function showByToken(Request $request, string $token): Response
    {
        return $this->resolve((string) $request->query('reference', ''), $token);
    }

    public function status(Request $request, string $reference): JsonResponse
    {
        $token = (string) ($request->header('X-Tracking-Token') ?: $request->query('token', ''));
        $trackingToken = $this->validToken($token);
        $report = $trackingToken?->report;

        if (! $report || ! hash_equals($report->reference_code, $reference)) {
            return $this->invalidStatusResponse();
        }

        return response()->json([
            'reference_code' => $report->reference_code,
            'status' => $report->status,
            'submitted_at' => $report->submitted_at?->toISOString(),
            'expires_at' => $report->expires_at?->toISOString(),
            'timeline' => $this->timeline($report),
            'public_updates' => $this->publicUpdates($report),
            'message_thread' => $this->messageThread($report),
        ])->withHeaders($this->privateTrackingHeaders());
    }

    protected function resolve(string $reference, string $token): Response
    {
        $trackingToken = $this->validToken($token);
        $report = $trackingToken?->report;

        if (! $report || $reference === '' || ! hash_equals($report->reference_code, $reference)) {
            return response()->view('tracking.invalid', [
                'message' => __('This tracking link is invalid or has expired.'),
            ], 404)->withHeaders($this->privateTrackingHeaders());
        }

        return response()->view('tracking.show', [
            'tracking_token' => $token,
            'thread_closed' => (bool) $report->thread_closed,
            'reference_code' => $report->reference_code,
            'submitted_at' => $report->submitted_at,
            'status' => $report->status,
            'expires_at' => $report->expires_at,
            'has_photo' => (bool) $report->photo_path,
            'timeline' => $this->timeline($report),
            'public_updates' => $this->publicUpdates($report),
            'message_thread' => $this->messageThread($report),
        ])->withHeaders($this->privateTrackingHeaders());
    }

    private function validToken(string $token): ?ReportTrackingToken
    {
        if ($token === '') {
            return null;
        }

        $trackingToken = ReportTrackingToken::query()
            ->with('report.statusEvents')
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (
            ! $trackingToken
            || $trackingToken->revoked_at
            || ($trackingToken->expires_at && $trackingToken->expires_at->isPast())
        ) {
            return null;
        }

        return $trackingToken;
    }

    private function timeline(IncidentReport $report): array
    {
        $events = $report->statusEvents->sortBy('created_at')->values();

        if ($events->isEmpty()) {
            $events = collect([[
                'status' => 'submitted',
                'created_at' => $report->submitted_at,
            ]]);

            if ($report->status !== 'submitted') {
                $events->push([
                    'status' => $report->status,
                    'created_at' => $report->updated_at,
                ]);
            }
        }

        return $events->map(fn ($event) => [
            'status' => $event['status'] ?? $event->status,
            'label' => $this->statusLabel($event['status'] ?? $event->status),
            'timestamp' => ($event['created_at'] ?? $event->created_at)?->toISOString(),
        ])->all();
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'submitted' => __('Submitted'),
            'in_progress' => __('In progress'),
            'resolved' => __('Resolved'),
            'closed' => __('Closed'),
            default => __(Str::headline($status)),
        };
    }

    private function messageThread(IncidentReport $report): array
    {
        return $report->messages()->orderBy('created_at')->get()->map(fn (ReportMessage $message) => [
            'id' => $message->id,
            'sender_type' => $message->sender_type,
            'body' => $message->body,
            'created_at' => $message->created_at?->toISOString(),
        ])->all();
    }

    private function publicUpdates(IncidentReport $report): array
    {
        return $report->messages()
            ->where('sender_type', 'staff')
            ->orderBy('created_at')
            ->get()
            ->map(fn (ReportMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'created_at' => $message->created_at?->toISOString(),
            ])->all();
    }

    private function invalidStatusResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'This tracking link is invalid or has expired.',
        ], 404)->withHeaders($this->privateTrackingHeaders());
    }

    private function privateTrackingHeaders(): array
    {
        return [
            'Cache-Control' => 'private, no-store',
            'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
            'X-Content-Type-Options' => 'nosniff',
        ];
    }
}
