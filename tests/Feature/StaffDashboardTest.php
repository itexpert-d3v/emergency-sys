<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\ReportTrackingToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
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

        $this->post(route('login'), [
            'email' => 'duty@example.gov.ph',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->actingAs($user)
            ->get('/queue')
            ->assertOk()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
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
            ->post('/reports/' . $report->id . '/triage', [
                'status' => 'in_progress',
                'priority' => 'high',
            ])
            ->assertRedirect('/queue');

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'status' => 'in_progress',
            'priority' => 'high',
        ]);
    }

    public function test_admin_can_assign_incident_type_and_save_private_call_remarks(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-triage@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency'],
            'active' => true,
            'sort_order' => 10,
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-200001',
            'description' => 'A resident needs urgent assistance near the town market.',
            'location_text' => 'Town market',
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

        $this->actingAs($admin)
            ->post('/reports/' . $report->id . '/triage', [
                'category_id' => $category->id,
                'remarks' => 'Reporter confirms the road is clear for responders.',
                'return_to' => 'admin',
            ])
            ->assertRedirect('/admin/dashboard/queue');

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'category_id' => $category->id,
            'remarks' => 'Reporter confirms the road is clear for responders.',
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertSee('Incident type')
            ->assertSee('Internal remarks')
            ->assertSee('Reporter confirms the road is clear for responders.');
    }

    public function test_accepting_one_report_clears_it_and_new_reports_still_need_acceptance(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-accept@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-200002',
            'description' => 'A resident needs assistance near the town market entrance.',
            'location_text' => 'Town market',
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

        $acceptResponse = $this->actingAs($admin)
            ->postJson('/admin/reports/' . $report->id . '/accept')
            ->assertOk()
            ->assertJsonPath('accepted', true)
            ->assertJsonPath('redirect_url', route('admin.dashboard.queue', ['expand' => $report->id]));

        $this->assertDatabaseMissing('incident_reports', ['id' => $report->id, 'accepted_at' => null]);
        $this->get($acceptResponse->json('redirect_url'))
            ->assertOk()
            ->assertSee('id="queue-item-' . $report->id . '"', false)
            ->assertSee('A resident needs assistance near the town market entrance.');

        $this->postJson('/admin/reports/' . $report->id . '/accept')
            ->assertOk()
            ->assertJsonPath('accepted', false);

        $newReport = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-200003',
            'description' => 'A second resident needs assistance near the town market entrance.',
            'location_text' => 'Town market',
            'reporter_name' => 'Jose Santos',
            'contact_number' => '+639181234568',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => 'closed',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->getJson('/live-map/data')
            ->assertOk()
            ->assertJsonCount(1, 'pending_reports')
            ->assertJsonPath('pending_reports.0.id', $newReport->id);
    }

    public function test_admin_can_accept_report_from_queue_and_returns_to_expanded_report(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-queue-accept@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-300003',
            'description' => 'A resident reports a damaged roadside sign near the town market.',
            'location_text' => 'Town market',
            'reporter_name' => 'Juan Santos',
            'contact_number' => '+639181234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'low',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->post('/admin/reports/' . $report->id . '/accept')
            ->assertRedirect(route('admin.dashboard.queue', ['expand' => $report->id]));

        $this->assertNotNull($report->fresh()->accepted_at);
    }

    public function test_live_map_returns_report_review_details_without_a_queue_or_message_feed(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-live-feed@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-200004',
            'description' => 'A resident reports a fallen power line beside the road.',
            'location_text' => 'Near the public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'high',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        $response = $this->actingAs($admin)->getJson('/live-map/data')->assertOk();

        $response->assertJsonCount(1, 'reports')
            ->assertJsonPath('reports.0.id', $report->id)
            ->assertJsonPath('reports.0.lat', null)
            ->assertJsonCount(1, 'pending_reports')
            ->assertJsonPath('pending_reports.0.description', 'A resident reports a fallen power line beside the road.')
            ->assertJsonPath('pending_reports.0.reporter_name', 'Maria Dela Cruz')
            ->assertJsonPath('pending_reports.0.contact_number', '+639171234567')
            ->assertJsonMissingPath('messages');
    }

    public function test_multiple_admin_workstations_can_accept_different_reports_from_the_backlog(): void
    {
        $firstAdmin = User::query()->create([
            'name' => 'First Admin',
            'email' => 'first-live-admin@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $secondAdmin = User::query()->create([
            'name' => 'Second Admin',
            'email' => 'second-live-admin@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $reportIds = [];

        foreach (['MDR-2026-200005', 'MDR-2026-200006'] as $referenceCode) {
            $reportIds[] = IncidentReport::query()->create([
                'reference_code' => $referenceCode,
                'description' => 'A resident needs an incident response near the town market.',
                'location_text' => 'Town market',
                'reporter_name' => 'Ana Santos',
                'contact_number' => '+639181234567',
                'reporting_for_other' => false,
                'language' => 'en',
                'priority' => 'medium',
                'status' => 'submitted',
                'idempotency_key' => (string) Str::uuid(),
                'submitted_at' => now(),
                'expires_at' => now()->addDays(7),
            ])->id;
        }

        $this->actingAs($firstAdmin)
            ->postJson('/admin/reports/' . $reportIds[0] . '/accept')
            ->assertOk()
            ->assertJsonPath('accepted', true);

        $this->actingAs($secondAdmin)
            ->getJson('/live-map/data')
            ->assertOk()
            ->assertJsonCount(1, 'pending_reports')
            ->assertJsonPath('pending_reports.0.id', $reportIds[1]);

        $this->actingAs($secondAdmin)
            ->postJson('/admin/reports/' . $reportIds[1] . '/accept')
            ->assertOk()
            ->assertJsonPath('accepted', true);

        $this->assertDatabaseMissing('incident_reports', ['id' => $reportIds[0], 'accepted_at' => null]);
        $this->assertDatabaseMissing('incident_reports', ['id' => $reportIds[1], 'accepted_at' => null]);
    }

    public function test_admin_can_ban_and_unban_an_ip_and_bans_block_report_submissions(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-ip-ban@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post('/admin/ip-bans', ['ip_address' => '127.0.0.1'])
            ->assertRedirect('/admin/moderation');

        $this->assertDatabaseHas('banned_ips', ['ip_address' => '127.0.0.1']);
        $ban = \App\Models\BannedIp::query()->where('ip_address', '127.0.0.1')->firstOrFail();
        $this->assertTrue($ban->expires_at->between(now()->addHours(23)->addMinutes(59), now()->addHours(24)->addSeconds(1)));
        $this->assertTrue(RateLimiter::tooManyAttempts(\App\Models\BannedIp::limiterKey('127.0.0.1'), 1));
        $this->actingAs($admin)
            ->get('/admin/moderation')
            ->assertOk()
            ->assertSee('IP addresses')
            ->assertSee('127.0.0.1')
            ->assertSee('Expires')
            ->assertSee('Unban');

        $this->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the town market entrance.',
            'location_text' => 'Town market',
            'reporter_name' => 'Ana Santos',
            'contact_number' => '+639181234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '6f717bcb-29f1-46c9-8823-3dc507073db7',
        ])->assertForbidden();

        $this->actingAs($admin)
            ->delete('/admin/ip-bans/' . $ban->id)
            ->assertRedirect('/admin/moderation');

        $this->assertDatabaseMissing('banned_ips', ['ip_address' => '127.0.0.1']);
        $this->assertFalse(RateLimiter::tooManyAttempts(\App\Models\BannedIp::limiterKey('127.0.0.1'), 1));
    }

    public function test_queue_has_inline_ban_and_soft_delete_actions_and_moderation_can_restore_report(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-moderation@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-300001',
            'description' => 'A resident reports a fallen power line by the market road.',
            'location_text' => 'Market road',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporter_ip' => '198.51.100.20',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'high',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertSee('Ban phone +639171234567')
            ->assertSee('Ban IP 198.51.100.20')
            ->assertSee('Accept report')
            ->assertSee('Soft delete report');

        $this->post('/admin/phone-bans', [
            'phone_number' => $report->contact_number,
            'report_id' => $report->id,
        ])->assertRedirect(route('admin.dashboard.queue', ['expand' => $report->id]));
        $this->assertDatabaseHas('banned_phones', ['phone_number' => '639171234567']);

        $this->post('/admin/ip-bans', [
            'ip_address' => $report->reporter_ip,
            'report_id' => $report->id,
        ])->assertRedirect(route('admin.dashboard.queue', ['expand' => $report->id]));
        $this->assertDatabaseHas('banned_ips', ['ip_address' => '198.51.100.20']);

        $this->post('/admin/reports/' . $report->id . '/soft-delete')
            ->assertRedirect('/admin/moderation');
        $this->assertSoftDeleted('incident_reports', ['id' => $report->id]);

        $this->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertDontSee($report->reference_code);

        $this->get('/admin/moderation')
            ->assertOk()
            ->assertSee('Deleted reports')
            ->assertSee($report->reference_code)
            ->assertSee('+639171234567')
            ->assertSee('198.51.100.20')
            ->assertSee('Restore report');

        $bannedPhone = \App\Models\BannedPhone::query()->where('phone_number', '639171234567')->firstOrFail();
        $this->delete('/admin/phone-bans/' . $bannedPhone->id)
            ->assertRedirect('/admin/moderation');
        $this->assertDatabaseMissing('banned_phones', ['phone_number' => '639171234567']);

        $this->post('/admin/moderation/reports/' . $report->id . '/restore')
            ->assertRedirect('/admin/moderation');
        $this->assertNotSoftDeleted('incident_reports', ['id' => $report->id]);

        $this->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertSee($report->reference_code);
    }

    public function test_admin_can_reject_and_reopen_a_report_with_reason_history(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-reject-report@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $report = IncidentReport::query()->create([
            'reference_code' => 'MDR-2026-300002',
            'description' => 'A resident reports a damaged roadside sign.',
            'location_text' => 'Main road',
            'reporter_name' => 'Juan Santos',
            'contact_number' => '+639181234567',
            'reporter_ip' => '198.51.100.21',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'low',
            'status' => 'submitted',
            'idempotency_key' => (string) Str::uuid(),
            'submitted_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $this->actingAs($admin)
            ->post('/admin/reports/' . $report->id . '/reject', [
                'rejection_reason' => 'Duplicate report; existing case MDR-2026-300001 is active.',
            ])
            ->assertRedirect('/admin/moderation');

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'status' => 'rejected',
            'rejection_reason' => 'Duplicate report; existing case MDR-2026-300001 is active.',
        ]);
        $this->assertDatabaseHas('report_status_events', [
            'report_id' => $report->id,
            'status' => 'rejected',
            'actor_user_id' => $admin->id,
        ]);
        $this->getJson('/live-map/data')
            ->assertOk()
            ->assertJsonMissing(['id' => $report->id]);
        $this->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertDontSee($report->reference_code);

        $this->get('/admin/moderation')
            ->assertOk()
            ->assertSee('Rejected reports')
            ->assertSee($report->reference_code)
            ->assertSee('Duplicate report; existing case MDR-2026-300001 is active.')
            ->assertSee('Reopen report');

        $this->post('/admin/moderation/reports/' . $report->id . '/reopen')
            ->assertRedirect('/admin/moderation');

        $this->assertDatabaseHas('incident_reports', [
            'id' => $report->id,
            'status' => 'submitted',
            'rejection_reason' => null,
            'rejected_at' => null,
        ]);
        $this->assertDatabaseHas('report_status_events', [
            'report_id' => $report->id,
            'status' => 'submitted',
            'actor_user_id' => $admin->id,
        ]);
    }

    public function test_dashboard_links_to_settings_without_loading_the_ip_ban_list(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin-dashboard-settings@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('href="' . route('admin.settings') . '"', false)
            ->assertDontSee('Banned IP addresses');
    }

    public function test_guest_landing_page_caters_to_residents_and_staff_access(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Residents')
            ->assertSee('Report an incident')
            ->assertSee('Track your report')
            ->assertSee('Operations sign in')
            ->assertDontSee('Admin login');
    }

    public function test_admin_can_log_in_and_view_the_admin_dashboard(): void
    {
        User::query()->create([
            'name' => 'System Admin',
            'email' => 'admin@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->post(route('login'), [
            'email' => 'admin@example.gov.ph',
            'password' => 'secret123',
        ])->assertRedirect('/dashboard');

        $this->actingAs(User::query()->first())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Command center')
            ->assertSee('Settings');
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
