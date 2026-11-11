<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

class ReportTrackingSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new RuntimeException('Demo report data can only be seeded in local or testing environments.');
        }

        $category = IncidentCategory::query()->active()->first();
        if (! $category) {
            throw new RuntimeException('No active incident categories found. Run DatabaseSeeder first.');
        }

        $seedReports = [
            [
                'key' => 'demo-report-submitted',
                'token' => 'demo-submitted-token-2026',
                'reference_code' => 'MDR-' . now()->format('Y') . '-DEMO01',
                'description' => 'Demonstration report for a flooded access road. This is not a real emergency.',
                'location_text' => 'Demo access road, Barangay Poblacion',
                'reporter_name' => 'Demo Reporter One',
                'contact_number' => '+639171111111',
                'priority' => 'medium',
                'status' => 'submitted',
                'submitted_at' => now()->subDay(),
            ],
            [
                'key' => 'demo-report-progress',
                'token' => 'demo-progress-token-2026',
                'reference_code' => 'MDR-' . now()->format('Y') . '-DEMO02',
                'description' => 'Demonstration report for a blocked road requiring staff follow-up. This is not a real emergency.',
                'location_text' => 'Demo junction, Barangay Andagao',
                'reporter_name' => 'Demo Reporter Two',
                'contact_number' => '+639172222222',
                'priority' => 'high',
                'status' => 'in_progress',
                'submitted_at' => now()->subDays(2),
            ],
            [
                'key' => 'demo-report-resolved',
                'token' => 'demo-resolved-token-2026',
                'reference_code' => 'MDR-' . now()->format('Y') . '-DEMO03',
                'description' => 'Demonstration report for a resolved medical assistance request. This is not a real emergency.',
                'location_text' => 'Demo community hall, Barangay Tigayon',
                'reporter_name' => 'Demo Reporter Three',
                'contact_number' => '+639173333333',
                'priority' => 'critical',
                'status' => 'resolved',
                'submitted_at' => now()->subDays(3),
            ],
        ];

        foreach ($seedReports as $seed) {
            $report = IncidentReport::query()->updateOrCreate(
                ['idempotency_key' => $seed['key']],
                [
                    'category_id' => $category->id,
                    'reference_code' => $seed['reference_code'],
                    'description' => $seed['description'],
                    'location_text' => $seed['location_text'],
                    'reporter_name' => $seed['reporter_name'],
                    'contact_number' => $seed['contact_number'],
                    'reporting_for_other' => false,
                    'language' => 'en',
                    'priority' => $seed['priority'],
                    'status' => $seed['status'],
                    'suspicion_flag' => false,
                    'submitted_at' => $seed['submitted_at'],
                    'expires_at' => now()->addDays(7),
                ],
            );

            $report->statusEvents()->firstOrCreate(['status' => 'submitted']);
            if ($seed['status'] !== 'submitted') {
                $report->statusEvents()->firstOrCreate(['status' => $seed['status']]);
            }

            $report->trackingTokens()->updateOrCreate(
                ['token_hash' => hash('sha256', $seed['token'])],
                ['expires_at' => $report->expires_at, 'revoked_at' => null],
            );

            $this->command?->line(sprintf(
                '%s → /track?reference=%s&token=%s',
                $report->reference_code,
                urlencode($report->reference_code),
                urlencode($seed['token']),
            ));
        }

        $this->command?->info('Seeded 3 demo reports with valid tracking tokens.');
    }
}
