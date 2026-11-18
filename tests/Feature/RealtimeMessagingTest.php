<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportTrackingToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeMessagingTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporter_can_send_a_message_with_a_tracking_token(): void
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency', 'fil' => 'Medikal na emerhensiya'],
            'active' => true,
            'sort_order' => 20,
        ]);

        $report = IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-000001',
            'description' => 'A resident reports a severe injury near the station.',
            'location_text' => 'Barangay Poblacion',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => 'submitted',
            'idempotency_key' => '7d83d1e8-3a45-4d33-bc0a-9dbd9d2f9d21',
            'expires_at' => now()->addDays(7),
            'submitted_at' => now(),
        ]);

        $token = 'token-12345';
        ReportTrackingToken::query()->create([
            'report_id' => $report->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        $this->post('/track/' . $token . '/messages', [
            'body' => 'We are on the way to the site.',
        ])->assertOk()
            ->assertJsonPath('sender_type', 'reporter')
            ->assertJsonPath('body', 'We are on the way to the site.');

        $this->assertDatabaseHas('report_messages', [
            'report_id' => $report->id,
            'sender_type' => 'reporter',
            'body' => 'We are on the way to the site.',
        ]);
    }

    public function test_staff_can_reply_to_and_close_a_report_thread(): void
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'fire-incident',
            'name_translations' => ['en' => 'Fire incident', 'fil' => 'Sunog'],
            'active' => true,
            'sort_order' => 10,
        ]);

        $report = IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-000002',
            'description' => 'Responders are checking a vehicle fire.',
            'location_text' => 'Barangay San Jose',
            'reporter_name' => 'Juan Santos',
            'contact_number' => '+639181112233',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'high',
            'status' => 'submitted',
            'idempotency_key' => 'a2b9873f-4f63-41c5-9c8d-1a4c0d1e6421',
            'expires_at' => now()->addDays(7),
            'submitted_at' => now(),
        ]);

        $user = User::query()->create([
            'name' => 'Duty Officer',
            'email' => 'duty@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);

        $this->actingAs($user)
            ->post('/reports/' . $report->id . '/messages', [
                'body' => 'We have dispatched a team. Please stay clear.',
            ])
            ->assertOk()
            ->assertJsonPath('sender_type', 'staff');

        $this->actingAs($user)
            ->post('/reports/' . $report->id . '/messages', [
                'body' => 'Thread closed by staff.',
                'close_thread' => true,
            ])
            ->assertOk();

        $this->assertDatabaseHas('report_messages', [
            'report_id' => $report->id,
            'sender_type' => 'staff',
            'body' => 'We have dispatched a team. Please stay clear.',
        ]);

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'status' => 'submitted',
        ]);
    }
}
