<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\BannedIp;
use App\Models\BannedPhone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class PublicReportingTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_report_page_loads_the_form_instead_of_the_placeholder(): void
    {
        $this->get('/report')
            ->assertOk()
            ->assertSee('Report an incident')
            ->assertSee('Submit a report')
            ->assertSee('required details complete')
            ->assertDontSee('Online report submission is not available yet.');
    }

    public function test_reporter_form_places_contact_before_location_and_does_not_select_incident_type(): void
    {
        $response = $this->get('/report')->assertOk();
        $html = $response->getContent();

        $this->assertLessThan(strpos($html, 'id="location-heading"'), strpos($html, 'id="contact-heading"'));
        $response->assertSee('0 of 4 required details complete')
            ->assertDontSee('name="category_id"', false);
    }

    public function test_it_lists_active_categories(): void
    {
        IncidentCategory::query()->create([
            'slug' => 'fire-incident',
            'name_translations' => ['en' => 'Fire incident', 'fil' => 'Sunog'],
            'active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonFragment(['slug' => 'fire-incident']);
    }

    public function test_it_uses_the_requested_registered_locale_for_category_names(): void
    {
        IncidentCategory::query()->create([
            'slug' => 'flooding',
            'name_translations' => [
                'en' => 'Flooding',
                'hil' => 'Baha',
                'krb' => 'Baha',
            ],
            'active' => true,
            'sort_order' => 10,
        ]);

        $this->getJson('/api/categories?locale=hil')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Baha']);
    }

    public function test_it_creates_a_report_and_reuses_the_same_reference_for_the_same_idempotency_key(): void
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency', 'fil' => 'Medikal na emerhensiya'],
            'active' => true,
            'sort_order' => 20,
        ]);

        $payload = [
            'category_id' => $category->id,
            'description' => 'A person collapsed near the market and needs help.',
            'location_text' => 'Barangay Poblacion, near the public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => 'fd0f09a6-85a8-4d3f-9f9e-cf6007ef4ab3',
        ];

        $first = $this->postJson('/api/reports', $payload);

        $first->assertOk()
            ->assertJsonPath('reference_code', $first->json('reference_code'))
            ->assertJsonPath('status', 'submitted');

        $second = $this->postJson('/api/reports', $payload);

        $second->assertOk()
            ->assertJsonPath('reference_code', $first->json('reference_code'));

        $this->assertDatabaseCount('incident_reports', 1);
    }

    public function test_public_submission_leaves_incident_type_unclassified_for_admin_triage(): void
    {
        $this->postJson('/api/reports', [
            'description' => 'A person needs assistance near the public market entrance.',
            'location_text' => 'Barangay Poblacion, public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '2c13bdf8-d7fb-4b9e-9fd1-1f54f1f832c1',
        ])->assertOk();

        $this->assertDatabaseHas('incident_reports', [
            'idempotency_key' => '2c13bdf8-d7fb-4b9e-9fd1-1f54f1f832c1',
            'category_id' => null,
            'reporter_ip' => '127.0.0.1',
        ]);
    }

    public function test_banned_phone_numbers_cannot_submit_new_reports(): void
    {
        BannedPhone::query()->create(['phone_number' => '639171234567']);

        $this->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the public market entrance.',
            'location_text' => 'Public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => 'fd3c43aa-8263-4fef-a71e-f3a54660c4ce',
        ])->assertForbidden();

        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_reporter_name_rejects_digits_and_unsafe_special_characters(): void
    {
        foreach (['Maria2 Cruz', '<script>alert(1)</script>', 'Maria@Cruz'] as $name) {
            $this->postJson('/api/reports', [
                'description' => 'A resident needs assistance near the public market entrance.',
                'location_text' => 'Public market',
                'reporter_name' => $name,
                'contact_number' => '+639171234567',
                'reporting_for_other' => false,
                'language' => 'en',
                'idempotency_key' => (string) \Illuminate\Support\Str::uuid(),
            ])->assertUnprocessable()->assertJsonValidationErrors('reporter_name');
        }
    }

    public function test_reporter_name_accepts_accents_apostrophes_and_hyphens(): void
    {
        $this->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the public market entrance.',
            'location_text' => 'Public market',
            'reporter_name' => "José O'Neil-Santos",
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '131575e5-2bf3-4176-9eaa-906c5d24b28a',
        ])->assertOk();

        $this->assertDatabaseHas('incident_reports', [
            'idempotency_key' => '131575e5-2bf3-4176-9eaa-906c5d24b28a',
            'reporter_name' => "José O'Neil-Santos",
        ]);
    }

    public function test_disguised_svg_script_content_is_rejected_as_a_raster_photo(): void
    {
        $this->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the public market entrance.',
            'location_text' => 'Public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => 'cbab6ad1-12fa-4aeb-8891-ae8a377da570',
            'photo' => UploadedFile::fake()->createWithContent('payload.jpg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        ])->assertUnprocessable()->assertJsonValidationErrors('photo');

        $this->assertDatabaseCount('incident_reports', 0);
    }

    public function test_expired_ip_ban_does_not_block_report_submission(): void
    {
        BannedIp::query()->create([
            'ip_address' => '127.0.0.1',
            'expires_at' => now()->subSecond(),
        ]);
        RateLimiter::hit(BannedIp::limiterKey('127.0.0.1'), 86400);

        $this->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the public market entrance.',
            'location_text' => 'Public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '7d502247-97a6-42bb-8e53-ccfb25aee30f',
        ])->assertOk();

        $this->assertDatabaseHas('incident_reports', [
            'idempotency_key' => '7d502247-97a6-42bb-8e53-ccfb25aee30f',
            'reporter_ip' => '127.0.0.1',
        ]);
        $this->assertFalse(RateLimiter::tooManyAttempts(BannedIp::limiterKey('127.0.0.1'), 1));
    }

    public function test_report_records_forwarded_client_ip_from_a_trusted_proxy(): void
    {
        $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_FOR' => '198.51.100.55',
        ])->postJson('/api/reports', [
            'description' => 'A resident needs assistance near the public market entrance.',
            'location_text' => 'Public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => 'b209dbe0-4b1f-4457-a539-e37c5f8c6616',
        ])->assertOk();

        $this->assertDatabaseHas('incident_reports', [
            'idempotency_key' => 'b209dbe0-4b1f-4457-a539-e37c5f8c6616',
            'reporter_ip' => '198.51.100.55',
        ]);
    }

    public function test_a_new_report_returns_a_private_tracking_link_that_loads(): void
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'road-incident',
            'name_translations' => ['en' => 'Road incident'],
            'active' => true,
            'sort_order' => 20,
        ]);

        $response = $this->postJson('/api/reports', [
            'category_id' => $category->id,
            'description' => 'A vehicle is blocking one lane near the public market.',
            'location_text' => 'Barangay Poblacion, near the public market',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234567',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => 'a0e25da0-507b-44b3-85d8-2a5f16cbbfd9',
        ])->assertOk();

        $this->get($response->json('tracking_url'))
            ->assertOk()
            ->assertSee($response->json('reference_code'))
            ->assertSee('Submitted')
            ->assertDontSee('+639171234567');
    }

    public function test_it_rejects_an_invalid_turnstile_token_when_configured(): void
    {
        config()->set('services.turnstile.secret_key', 'test-secret');

        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify*' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        $category = IncidentCategory::query()->create([
            'slug' => 'flooding',
            'name_translations' => ['en' => 'Flooding', 'fil' => 'Baha'],
            'active' => true,
            'sort_order' => 30,
        ]);

        $this->postJson('/api/reports', [
            'category_id' => $category->id,
            'description' => 'Flooding is affecting the road and residents need assistance immediately.',
            'location_text' => 'Barangay Poblacion, near the bridge',
            'reporter_name' => 'Jose Dela Cruz',
            'contact_number' => '+639171234568',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '117d4d35-7d6d-41af-9896-5c7bc729b4b8',
            'cf_turnstile_response' => 'invalid-token',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('cf_turnstile_response');
    }

    public function test_it_throttles_report_submission_requests(): void
    {
        $category = IncidentCategory::query()->create([
            'slug' => 'medical-emergency',
            'name_translations' => ['en' => 'Medical emergency', 'fil' => 'Medikal na emerhensiya'],
            'active' => true,
            'sort_order' => 20,
        ]);

        $payload = [
            'category_id' => $category->id,
            'description' => 'A resident is experiencing dizziness and needs urgent assistance.',
            'location_text' => 'Barangay Poblacion, health center',
            'reporter_name' => 'Maria Dela Cruz',
            'contact_number' => '+639171234569',
            'reporting_for_other' => false,
            'language' => 'en',
            'idempotency_key' => '8ae2eb32-0dd2-4a5e-a4f2-a5db3bb0c67c',
        ];

        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/reports', $payload + ['idempotency_key' => 'rate-limit-' . $i])
                ->assertOk();
        }

        $this->postJson('/api/reports', $payload + ['idempotency_key' => 'rate-limit-3'])
            ->assertStatus(429);
    }
}
