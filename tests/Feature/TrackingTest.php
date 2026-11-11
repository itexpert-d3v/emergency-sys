<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportTrackingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reference_without_token_is_rejected(): void
    {
        $report = $this->createReport();

        $this->get('/track?reference=' . $report->reference_code)
            ->assertStatus(404)
            ->assertSee('This tracking link is invalid or has expired.');
    }

    public function test_valid_token_shows_tracking_details_without_contact_information(): void
    {
        $report = $this->createReport();
        $token = 'tracking-token-' . Str::random(32);

        ReportTrackingToken::query()->create([
            'id' => (string) Str::ulid(),
            'report_id' => $report->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $this->get('/track?reference=' . $report->reference_code . '&token=' . $token)
            ->assertOk()
            ->assertSee($report->reference_code)
            ->assertSee('Submitted')
            ->assertDontSee($report->contact_number);

        $this->getJson('/api/reports/' . $report->reference_code . '/status?token=' . $token)
            ->assertOk()
            ->assertJsonPath('reference_code', $report->reference_code)
            ->assertJsonPath('status', 'submitted');
    }

    public function test_expired_token_is_indistinguishable_from_an_invalid_tracking_link(): void
    {
        $report = $this->createReport();
        $token = 'expired-tracking-token-' . Str::random(32);

        ReportTrackingToken::query()->create([
            'id' => (string) Str::ulid(),
            'report_id' => $report->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->subDay(),
        ]);

        $expired = $this->get('/track?reference=' . $report->reference_code . '&token=' . $token)
            ->assertStatus(404)
            ->assertSee('This tracking link is invalid or has expired.')
            ->assertDontSee($report->reference_code);

        $invalid = $this->get('/track?reference=' . $report->reference_code . '&token=wrong-token')
            ->assertStatus(404);

        $this->assertSame($expired->getContent(), $invalid->getContent());
    }

    public function test_revoked_token_uses_the_same_non_disclosing_response(): void
    {
        $report = $this->createReport();
        $token = 'revoked-tracking-token-' . Str::random(32);

        ReportTrackingToken::query()->create([
            'id' => (string) Str::ulid(),
            'report_id' => $report->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $this->get('/track?reference=' . $report->reference_code . '&token=' . $token)
            ->assertStatus(404)
            ->assertSee('This tracking link is invalid or has expired.')
            ->assertDontSee($report->reference_code);
    }

    private function createReport(): IncidentReport
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'fire-incident',
            'name_translations' => ['en' => 'Fire incident', 'fil' => 'Sunog'],
            'active' => true,
            'sort_order' => 10,
        ]);

        return IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-000001',
            'description' => 'A fire broke out near the market.',
            'location_text' => 'Barangay Poblacion',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
    }
}
