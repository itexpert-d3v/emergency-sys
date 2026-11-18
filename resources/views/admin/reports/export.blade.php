<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; color: #102a34; font-size: 9px; }
        h1 { font-size: 18px; margin: 0 0 5px; }
        p { color: #49616a; margin: 0 0 16px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #b8c9cc; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #e6f1f0; }
    </style>
</head>
<body>
    <h1>Incident system report</h1>
    <p>{{ $period['start']->format('M j, Y') }} to {{ $period['end']->format('M j, Y') }} · {{ $reports->count() }} records</p>
    <table>
        <thead><tr><th>Reference</th><th>Category</th><th>Status</th><th>Priority</th><th>Submitted</th><th>Location</th></tr></thead>
        <tbody>
        @forelse ($reports as $report)
            <tr><td>{{ $report->reference_code }}</td><td>{{ $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report' }}</td><td>{{ ucfirst(str_replace('_', ' ', $report->status)) }}</td><td>{{ ucfirst($report->priority) }}</td><td>{{ $report->submitted_at?->format('M j, Y g:i A') ?? '—' }}</td><td>{{ $report->location_text ?: 'Not provided' }}</td></tr>
        @empty
            <tr><td colspan="6">No incident reports for the selected period.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>