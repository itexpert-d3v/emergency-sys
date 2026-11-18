<?php

namespace App\Http\Controllers;

use App\Models\IncidentReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

class ReportingController extends Controller
{
    private const EXPORT_LIMIT = 10000;

    public function index(Request $request): View
    {
        $period = $this->period($request);
        $query = $this->query($period['start'], $period['end']);
        $statusCounts = (clone $query)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('admin.reports.index', [
            'period' => $period,
            'total' => (clone $query)->count(),
            'statusCounts' => $statusCounts,
            'reports' => (clone $query)->with('category')->orderByDesc('submitted_at')->paginate(50)->withQueryString(),
        ]);
    }

    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'xlsx', 'docx'], true), 404);

        $period = $this->period($request);
        $reports = $this->query($period['start'], $period['end'])
            ->with('category')
            ->orderByDesc('submitted_at')
            ->limit(self::EXPORT_LIMIT)
            ->get();
        $filename = 'incident-report-' . $period['start']->format('Ymd') . '-' . $period['end']->format('Ymd');

        return match ($format) {
            'pdf' => Pdf::loadView('admin.reports.export', compact('period', 'reports'))->setPaper('a4', 'landscape')->download($filename . '.pdf'),
            'xlsx' => $this->exportSpreadsheet($period, $reports, $filename . '.xlsx'),
            'docx' => $this->exportWord($period, $reports, $filename . '.docx'),
        };
    }

    private function period(Request $request): array
    {
        $data = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly'],
            'date' => ['nullable', 'date_format:Y-m-d'],
        ]);
        $period = $data['period'] ?? 'daily';
        $date = CarbonImmutable::parse($data['date'] ?? now()->toDateString());

        [$start, $end] = match ($period) {
            'weekly' => [$date->startOfWeek(CarbonImmutable::MONDAY), $date->endOfWeek(CarbonImmutable::SUNDAY)],
            'monthly' => [$date->startOfMonth(), $date->endOfMonth()],
            default => [$date->startOfDay(), $date->endOfDay()],
        };

        return compact('period', 'start', 'end');
    }

    private function query(CarbonImmutable $start, CarbonImmutable $end)
    {
        return IncidentReport::query()->whereBetween('submitted_at', [$start, $end]);
    }

    private function exportSpreadsheet(array $period, Collection $reports, string $filename)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Incident report');
        $sheet->fromArray(['Reference', 'Category', 'Status', 'Priority', 'Submitted', 'Location'], null, 'A1');
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        foreach ($this->rows($reports) as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueExplicit([$columnIndex + 1, $rowIndex + 2], (string) $value, DataType::TYPE_STRING);
            }
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(
            fn () => (new Xlsx($spreadsheet))->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    private function exportWord(array $period, Collection $reports, string $filename)
    {
        $document = new PhpWord();
        $section = $document->addSection(['orientation' => 'landscape']);
        $section->addTitle('Incident system report', 1);
        $section->addText($period['start']->format('M j, Y') . ' to ' . $period['end']->format('M j, Y'));
        $table = $section->addTable(['borderSize' => 6, 'borderColor' => 'B8C9CC', 'cellMargin' => 80]);
        $table->addRow();
        foreach (['Reference', 'Category', 'Status', 'Priority', 'Submitted', 'Location'] as $heading) {
            $table->addCell(2200, ['bgColor' => 'E6F1F0'])->addText($heading, ['bold' => true]);
        }
        foreach ($this->rows($reports) as $row) {
            $table->addRow();
            foreach ($row as $value) {
                $table->addCell(2200)->addText((string) $value);
            }
        }

        return response()->streamDownload(
            fn () => IOFactory::createWriter($document, 'Word2007')->save('php://output'),
            $filename,
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
        );
    }

    private function rows(Collection $reports): array
    {
        return $reports->map(fn (IncidentReport $report) => [
            $report->reference_code,
            $report->category?->nameFor(app()->getLocale(), 'en') ?? 'Incident report',
            str_replace('_', ' ', $report->status),
            $report->priority,
            $report->submitted_at?->format('Y-m-d H:i:s') ?? '',
            $report->location_text,
        ])->all();
    }
}