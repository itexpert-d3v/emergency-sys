<?php

namespace Tests\Feature;

use Tests\TestCase;

class PublicSafetyCopyTest extends TestCase
{
    public function test_public_language_switcher_loads_a_locale_from_its_json_file(): void
    {
        $this->from('/report')->post('/language', ['locale' => 'hil'])
            ->assertSessionHas('locale', 'hil');

        $this->get('/report')
            ->assertOk()
            ->assertSee('<html lang="hil">', false)
            ->assertSee('Ireport ang insidente');
    }

    public function test_public_language_switcher_offers_the_four_required_languages(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('value="en"', false)
            ->assertSee('value="fil"', false)
            ->assertSee('value="hil"', false)
            ->assertSee('value="krb"', false);
    }

    public function test_public_language_switcher_loads_filipino_copy(): void
    {
        $this->from('/report')->post('/language', ['locale' => 'fil'])
            ->assertSessionHas('locale', 'fil');

        $this->get('/report')
            ->assertOk()
            ->assertSee('Mag-ulat ng insidente');
    }

    public function test_public_language_switcher_rejects_locales_without_translation_files(): void
    {
        $this->from('/')->post('/language', ['locale' => 'xx'])
            ->assertSessionHasErrors('locale');
    }

    public function test_public_pages_do_not_render_the_repeated_hotline_notice(): void
    {
        foreach (['/', '/report'] as $path) {
            $response = $this->get($path)->assertOk();

            $response->assertDontSee('Call your local emergency hotline now.')
                ->assertDontSee('Life-threatening emergency?');
        }

        $trackingViews = [
            'tracking.show' => [
                'reference_code' => 'TEST-123', 'submitted_at' => null, 'status' => 'submitted',
                'timeline' => [], 'public_updates' => [], 'message_thread' => [], 'expires_at' => null,
            ],
            'tracking.invalid' => ['message' => 'Tracking unavailable.'],
            'tracking.expired' => ['message' => 'Tracking unavailable.'],
        ];

        foreach ($trackingViews as $view => $data) {
            $html = view($view, $data)->render();
            $this->assertStringNotContainsString('Call your local emergency hotline now.', $html);
            $this->assertStringNotContainsString('Life-threatening emergency?', $html);
            $this->assertStringNotContainsString('<script', $html);
        }
    }

    public function test_public_pages_do_not_load_third_party_scripts_or_claim_response_times(): void
    {
        foreach (['/', '/report'] as $path) {
            $response = $this->get($path)->assertOk();
            $response->assertDontSee('cdn.tailwindcss.com')
                ->assertDontSee('<script src="https://')
                ->assertDontSee('<script')
                ->assertDontSee('15 min')
                ->assertDontSee('24/7');
        }
    }
}
