<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use RuntimeException;
use Tests\TestCase;

class ErrorPagesTest extends TestCase
{
    public function test_missing_page_uses_the_custom_not_found_view(): void
    {
        $this->get('/page-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('Page not found')
            ->assertSee('Return to home')
            ->assertSee('Kalibo MDRRMO');
    }

    public function test_forbidden_response_uses_the_custom_restricted_view(): void
    {
        Route::get('/__test/forbidden', fn () => abort(403));

        $this->get('/__test/forbidden')
            ->assertForbidden()
            ->assertSee('This area is restricted')
            ->assertSee('Staff sign in');
    }

    public function test_server_error_uses_a_generic_view_without_exposing_exception_details(): void
    {
        config(['app.debug' => false]);
        Route::get('/__test/server-error', fn () => throw new RuntimeException('private database detail'));

        $this->get('/__test/server-error')
            ->assertInternalServerError()
            ->assertSee('We could not complete that request')
            ->assertDontSee('private database detail');
    }

    public function test_service_unavailable_uses_the_custom_maintenance_view(): void
    {
        Route::get('/__test/maintenance', fn () => abort(503));

        $this->get('/__test/maintenance')
            ->assertStatus(503)
            ->assertSee('The service is temporarily unavailable')
            ->assertSee('local emergency hotline');
    }
}
