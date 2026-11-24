<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_and_worker_are_public_with_expected_content(): void
    {
        $this->get('/manifest.webmanifest')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json');

        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('MDRRMO SOU', $manifest['short_name']);

        $this->get('/sw.js')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/javascript; charset=utf-8');

        $worker = file_get_contents(public_path('sw.js'));
        $this->assertStringContainsString('PRIVATE_PREFIXES', $worker);
        $this->assertStringContainsString('skipWaiting', $worker);

        $this->get('/offline.html')
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        $offline = file_get_contents(public_path('offline.html'));
        $this->assertStringContainsString('You are offline', $offline);
    }

    public function test_public_layout_includes_pwa_metadata(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('rel="manifest"', false)
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('/assets/apple-touch-icon.png', false);
    }

    public function test_authenticated_layout_responses_are_private_no_store(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)
            ->get('/dashboard')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');

        $this->actingAs($staff)
            ->get('/profile')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_tracking_layout_remains_noindex_and_private_no_store(): void
    {
        $this->get('/track')->assertNotFound()
            ->assertSee('name="robots" content="noindex,nofollow"', false)
            ->assertHeader('Cache-Control', 'no-store, private');
    }
}
