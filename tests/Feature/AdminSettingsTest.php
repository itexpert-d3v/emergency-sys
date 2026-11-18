<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_settings_page_has_a_persisted_workstation_alarm_toggle(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'settings-admin@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/admin/settings')
            ->assertOk()
            ->assertSee('Audible alarm on this workstation')
            ->assertSee("admin-alarm-sound-enabled", false)
            ->assertSee('Upload MP3')
            ->assertSee('Settings')
            ->assertSee('Report intake')
            ->assertSee('Bans and deleted reports')
            ->assertSee('Appearance')
            ->assertSee('data-appearance-choice="light"', false)
            ->assertSee('data-appearance-choice="dark"', false)
            ->assertSee('data-appearance-choice="system"', false)
            ->assertSee(route('admin.moderation.index'));
    }

    public function test_admin_can_update_report_intake_limits(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'settings-limits@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->post('/admin/settings/report-intake', [
                'description_min_length' => 30,
                'description_max_length' => 1800,
                'reporter_name_max_length' => 90,
                'expiry_days' => 10,
            ])
            ->assertRedirect('/admin/settings');

        $this->assertSame(30, Setting::get('report.description_min_length'));
        $this->assertSame(1800, Setting::get('report.description_max_length'));
        $this->assertSame(90, Setting::get('report.reporter_name_max_length'));
        $this->assertSame(10, Setting::get('report.expiry_days'));
    }

    public function test_report_intake_limits_reject_maximum_below_minimum(): void
    {
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'settings-invalid-limits@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->from('/admin/settings')
            ->post('/admin/settings/report-intake', [
                'description_min_length' => 100,
                'description_max_length' => 50,
                'reporter_name_max_length' => 90,
                'expiry_days' => 10,
            ])
            ->assertRedirect('/admin/settings')
            ->assertSessionHasErrors('description_max_length');
    }

    public function test_admin_can_upload_play_and_remove_a_private_mp3_alarm(): void
    {
        Storage::fake('local');
        $admin = User::query()->create([
            'name' => 'System Admin',
            'email' => 'settings-audio@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'admin',
        ]);
        $audioBytes = "ID3" . "\x04\x00\x00" . str_repeat("\x00", 96);

        $this->actingAs($admin)
            ->post('/admin/settings/alarm-audio', [
                'alarm_audio' => UploadedFile::fake()->createWithContent('dispatch-alarm.mp3', $audioBytes),
            ])
            ->assertRedirect('/admin/settings');

        $path = Setting::get('admin.alarm_audio_path');
        $this->assertIsString($path);
        Storage::disk('local')->assertExists($path);

        $this->get('/live-map/alarm-audio')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->get('/admin/settings')
            ->assertOk()
            ->assertSee(route('operations.live-map.alarm-audio'), false);

        $this->delete('/admin/settings/alarm-audio')
            ->assertRedirect('/admin/settings');

        Storage::disk('local')->assertMissing($path);
        $this->assertNull(Setting::get('admin.alarm_audio_path'));
    }

    public function test_non_admin_cannot_access_or_upload_alarm_settings(): void
    {
        $staff = User::query()->create([
            'name' => 'Duty Officer',
            'email' => 'settings-staff@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'staff',
        ]);

        $this->actingAs($staff)
            ->get('/admin/settings')
            ->assertForbidden();
    }
}