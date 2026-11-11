<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportTrackingToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StaffDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_log_in_and_view_the_dashboard_queue(): void
    {
        $user = User::query()->create([
            'name' => 'Duty Officer',
            'email' => 'duty@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);

        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency', 'fil' => 'Medikal na emerhensiya'],
            'active' => true,
            'sort_order' => 20,
        ]);

        IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-100001',
            'description' => 'Patient collapsed at the terminal.',
            'location_text' => 'Terminal 1',
            'reporter_name' => 'Juan Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->post('/staff/login', [
            'email' => 'duty@example.gov.ph',
            'password' => 'secret123',
        ])->assertRedirect('/staff/dashboard');

        $this->actingAs($user)
            ->get('/staff/dashboard')
            ->assertOk()
            ->assertSee('MDR-2026-100001');
    }

    public function test_staff_can_update_a_report_status_from_the_dashboard(): void
    {
        $user = User::query()->create([
            'name' => 'Duty Officer',
            'email' => 'duty2@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);

        $category = IncidentCategory::query()->create([
            'slug' => 'fire-incident',
            'name_translations' => ['en' => 'Fire incident', 'fil' => 'Sunog'],
            'active' => true,
            'sort_order' => 10,
        ]);

        $report = IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-100002',
            'description' => 'Warehouse fire reported.',
            'location_text' => 'Barangay Labangon',
            'reporter_name' => 'Ana Santos',
            'contact_number' => '+639181234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($user)
            ->post('/staff/reports/' . $report->id . '/triage', [
                'status' => 'in_progress',
                'priority' => 'high',
            ])
            ->assertRedirect('/staff/dashboard');

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_guest_landing_page_caters_to_residents_and_staff_access(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Residents')
            ->assertSee('Report an incident')
            ->assertSee('Track your report')
            ->assertSee('Staff login')
            ->assertSee('Admin login');
    }

    public function test_admin_can_log_in_and_view_the_admin_dashboard(): void
    {
        User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->post('/admin/login', [
            'email' => 'admin@example.gov.ph',
            'password' => 'secret123',
        ])->assertRedirect('/admin/dashboard');

        $this->actingAs(User::query()->first())
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Admin panel');
    }

    public function test_only_an_admin_can_confirm_and_share_a_responder_dispatch(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-dispatch@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $staff = User::query()->create([
            'name' => 'Duty Officer',
            'email' => 'staff-dispatch@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);
        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency'],
            'active' => true,
            'sort_order' => 20,
        ]);
        $report = IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => 'MDR-2026-100003',
            'description' => 'A patient needs urgent medical assistance at the terminal.',
            'location_text' => 'Kalibo terminal',
            'reporter_name' => 'Ana Santos',
            'contact_number' => '+639181234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'high',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        $token = 'dispatch-tracking-token';
        ReportTrackingToken::query()->create([
            'report_id' => $report->id,
            'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($staff)
            ->post('/admin/reports/' . $report->id . '/dispatch', [
                'responder' => 'ambulance',
                'dispatch_confirmed' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($admin)
            ->post('/admin/reports/' . $report->id . '/dispatch', [
                'responder' => 'ambulance',
                'dispatch_confirmed' => '1',
            ])
            ->assertRedirect('/admin/dashboard/queue');

        $this->assertDatabaseHas('incident_reports', ['id' => $report->id, 'status' => 'in_progress']);
        $this->assertDatabaseHas('report_messages', [
            'report_id' => $report->id,
            'sender_type' => 'staff',
            'body' => 'MDRRMO update: An ambulance is on the way.',
        ]);

        $this->get('/track?reference=' . $report->reference_code . '&token=' . $token)
            ->assertOk()
            ->assertSee('MDRRMO update: An ambulance is on the way.');
    }
}
