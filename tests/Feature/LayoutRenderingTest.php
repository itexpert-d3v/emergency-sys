<?php

namespace Tests\Feature;

use Tests\TestCase;

class LayoutRenderingTest extends TestCase
{
    public function test_public_information_pages_use_the_shared_document_shell(): void
    {
        foreach (['/about', '/how-it-works'] as $path) {
            $response = $this->get($path)->assertOk();
            $html = $response->getContent();

            $this->assertSame(1, substr_count($html, '<html'));
            $this->assertSame(1, substr_count($html, '<head>'));
            $this->assertSame(1, substr_count($html, '<h1'));
            $response->assertSee('theme-color', false)
                ->assertDontSee('For immediate danger', false)
                ->assertDontSee('name="robots" content="noindex,nofollow"', false);
        }
    }

    public function test_offline_page_is_self_contained_and_includes_emergency_guidance(): void
    {
        $html = view('offline')->render();

        $this->assertStringContainsString('You are offline', $html);
        $this->assertStringContainsString('This website does not dispatch responders.', $html);
        $this->assertStringContainsString('<style>', $html);
        $this->assertStringNotContainsString('@vite', $html);
    }
}
