<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
