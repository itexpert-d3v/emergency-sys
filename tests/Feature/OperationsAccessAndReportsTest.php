<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\IncidentReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationsAccessAndReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_open_only_their_operations_modules(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get('/dashboard')->assertOk();
        $this->get('/live-map')->assertOk()->assertSee('style="display: none"', false);
        $this->get('/queue')->assertOk();
        $this->get('/news')->assertOk();
        $this->get('/profile')->assertOk();

        $this->get('/admin/settings')->assertForbidden();
        $this->get('/admin/moderation')->assertForbidden();
        $this->get('/admin/dashboard/queue')->assertForbidden();
        $this->get('/admin/users')->assertForbidden();
        $this->get('/admin/reports')->assertForbidden();
        $this->post('/admin/settings/report-intake', [
            'description_min_length' => 20,
            'description_max_length' => 2000,
            'reporter_name_max_length' => 120,
            'expiry_days' => 7,
        ])->assertForbidden();
    }

    public function test_staff_profile_updates_are_self_scoped_and_cannot_change_role(): void
    {
        $staff = User::factory()->create(['role' => 'staff', 'username' => 'duty.officer']);
        $other = User::factory()->create(['role' => 'staff', 'username' => 'other.officer']);

        $this->actingAs($staff)->put('/profile', [
            'username' => 'duty.updated',
            'name' => 'Duty Officer Updated',
            'email' => $staff->email,
            'role' => 'admin',
        ])->assertRedirect('/profile');

        $this->assertDatabaseHas('users', ['id' => $staff->id, 'username' => 'duty.updated', 'role' => 'staff']);
        $this->assertDatabaseHas('users', ['id' => $other->id, 'username' => 'other.officer', 'name' => $other->name]);
    }

    public function test_profile_avatar_upload_is_validated_and_publicly_stored(): void
    {
        Storage::fake('public');
        $staff = User::factory()->create(['role' => 'staff', 'username' => 'avatar.staff']);

        $this->actingAs($staff)->put('/profile', [
            'username' => $staff->username,
            'name' => $staff->name,
            'email' => $staff->email,
            'avatar' => UploadedFile::fake()->image('portrait.jpg'),
        ])->assertRedirect('/profile');

        $avatarPath = $staff->fresh()->avatar_path;
        $this->assertIsString($avatarPath);
        Storage::disk('public')->assertExists($avatarPath);
        $this->get('/profile')->assertOk()->assertSee(asset('storage/' . $avatarPath), false);

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('operations-account-menu', false)
            ->assertSee('operations-user-avatar', false)
            ->assertSee(asset('storage/' . $avatarPath), false);
    }

    public function test_admin_user_management_can_create_and_assign_roles_but_preserves_an_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('System users');
        $this->get('/admin/users/create')->assertOk()->assertSee('Create user');

        $this->actingAs($admin)
            ->post('/admin/users', [
                'username' => 'new.duty.officer',
                'name' => 'New Duty Officer',
                'email' => 'new-duty@example.gov.ph',
                'role' => 'staff',
                'password' => 'secure-password-123',
                'password_confirmation' => 'secure-password-123',
            ])
            ->assertRedirect('/admin/users');

        $newUser = User::query()->where('email', 'new-duty@example.gov.ph')->firstOrFail();
        $this->assertSame('staff', $newUser->role);
        $newUser->update(['avatar_path' => 'avatars/' . $newUser->id . '/portrait.webp']);
        $this->get('/admin/users')->assertOk()->assertSee(asset('storage/' . $newUser->avatar_path), false);

        $this->put('/admin/users/' . $admin->id, [
            'username' => $admin->username,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'staff',
            'password' => '',
            'password_confirmation' => '',
        ])->assertSessionHasErrors('role');

        $this->delete('/admin/users/' . $admin->id)->assertSessionHasErrors('user');
    }

    public function test_admin_reports_filter_daily_period_and_download_all_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $report = $this->report('MDR-2026-REPORT1', now());
        $this->report('MDR-2026-OLDER1', now()->subDay());
        $date = now()->toDateString();

        $this->actingAs($admin)
            ->get('/admin/reports?period=daily&date=' . $date)
            ->assertOk()
            ->assertSee($report->reference_code)
            ->assertDontSee('MDR-2026-OLDER1')
            ->assertSee('Excel (.xlsx)')
            ->assertSee('Word (.docx)');

        $this->get('/admin/reports?period=weekly&date=' . $date)
            ->assertOk()
            ->assertSee('MDR-2026-OLDER1');
        $this->get('/admin/reports?period=monthly&date=' . $date)
            ->assertOk()
            ->assertSee('MDR-2026-OLDER1');

        $expectedTypes = [
            'pdf' => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        foreach ($expectedTypes as $format => $contentType) {
            $this->get('/admin/reports/export/' . $format . '?period=daily&date=' . $date)
                ->assertOk()
                ->assertHeader('content-type', $contentType)
                ->assertDownload('incident-report-' . now()->format('Ymd') . '-' . now()->format('Ymd') . '.' . $format);
        }
    }

    public function test_staff_cannot_generate_or_view_admin_reports(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get('/admin/reports/export/pdf?period=daily')->assertForbidden();
    }

    public function test_incident_summary_cards_show_full_counts_and_completed_filter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->report('MDR-2026-QUEUE01', now(), 'submitted');
        $this->report('MDR-2026-QUEUE02', now(), 'in_progress');
        $this->report('MDR-2026-QUEUE03', now(), 'resolved');
        $this->report('MDR-2026-QUEUE04', now(), 'closed');

        $this->actingAs($admin)
            ->get('/admin/dashboard/queue')
            ->assertOk()
            ->assertSee('All reports')
            ->assertSee('Submitted')
            ->assertSee('In progress')
            ->assertSee('Completed')
            ->assertSee('status=completed', false);

        $this->get('/admin/dashboard/queue?status=completed')
            ->assertOk()
            ->assertSee('MDR-2026-QUEUE03')
            ->assertSee('MDR-2026-QUEUE04')
            ->assertDontSee('MDR-2026-QUEUE01');
    }

    public function test_sensitive_admin_writes_are_rate_limited(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->actingAs($admin)->post('/admin/users', [])->assertSessionHasErrors('username');
        }

        $this->actingAs($admin)->post('/admin/users', [])->assertTooManyRequests();
    }

    private function report(string $reference, $submittedAt, string $status = 'submitted'): IncidentReport
    {
        $category = IncidentCategory::query()->firstOrCreate(
            ['slug' => 'general-incident'],
            ['name_translations' => ['en' => 'General incident'], 'active' => true, 'sort_order' => 1]
        );

        return IncidentReport::query()->create([
            'category_id' => $category->id,
            'reference_code' => $reference,
            'description' => 'A sample report for access and export tests.',
            'location_text' => 'Kalibo town center',
            'reporter_name' => 'Example Reporter',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'priority' => 'medium',
            'status' => $status,
            'idempotency_key' => (string) Str::uuid(),
            'expires_at' => $submittedAt->copy()->addDays(7),
            'submitted_at' => $submittedAt,
        ]);
    }
}
