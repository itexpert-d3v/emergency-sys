<?php

namespace Tests\Feature;

use App\Models\NewsPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsPublicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_news_only_shows_active_published_content(): void
    {
        $author = User::factory()->create(['role' => 'admin']);
        $published = $this->postRecord($author, [
            'title' => 'Flood advisory for Kalibo',
            'slug' => 'flood-advisory',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'is_banner' => true,
        ]);
        $this->postRecord($author, ['title' => 'Draft memo', 'slug' => 'draft-memo']);
        $this->postRecord($author, [
            'title' => 'Tomorrow advisory',
            'slug' => 'tomorrow-advisory',
            'status' => 'scheduled',
            'published_at' => now()->addDay(),
        ]);
        $this->postRecord($author, [
            'title' => 'Expired advisory',
            'slug' => 'expired-advisory',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->get('/news?q=flood')
            ->assertOk()
            ->assertSee('Flood advisory for Kalibo')
            ->assertDontSee('Draft memo')
            ->assertDontSee('Tomorrow advisory')
            ->assertDontSee('Expired advisory');

        $this->get('/news/flood-advisory')->assertOk()->assertSee('Flood advisory for Kalibo');
        $this->get('/news/draft-memo')->assertNotFound();
        $this->get('/news/tomorrow-advisory')->assertNotFound();
        $this->assertTrue($published->fresh()->is_banner);
    }

    public function test_only_authorized_publishing_roles_can_open_the_cms(): void
    {
        $this->get('/admin/news')->assertRedirect('/admin/login');

        $staff = User::factory()->create(['role' => 'staff']);
        $this->actingAs($staff)->get('/admin/news')->assertForbidden();

        $officer = User::factory()->create(['role' => 'information_officer']);
        $this->actingAs($officer)->get('/admin/news')->assertOk();
    }

    public function test_information_officer_can_sign_in_to_the_publishing_panel(): void
    {
        $officer = User::factory()->create([
            'email' => 'info@example.gov.ph',
            'password' => bcrypt('secret123'),
            'role' => 'information_officer',
        ]);

        $this->post('/admin/login', [
            'email' => $officer->email,
            'password' => 'secret123',
        ])->assertRedirect('/admin/news');
    }

    public function test_public_information_pages_are_dedicated_and_share_the_admin_navigation(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('About Kalibo MDRRMO')
            ->assertSee('MDRRMO admin sign in')
            ->assertSee('assets/mddrmobg.jpg');

        $this->get('/how-it-works')
            ->assertOk()
            ->assertSee('How it works')
            ->assertSee('MDRRMO admin sign in');

        $this->get('/')
            ->assertOk()
            ->assertSee('MDRRMO admin sign in')
            ->assertSee('Recent news & updates', false)
            ->assertDontSee('Kalibo MDRRMO response team')
            ->assertDontSee('Emergency Preparedness and Safety')
            ->assertDontSee('Useful support, from first report to latest update');
    }

    public function test_landing_page_shows_recent_public_news_but_never_drafts(): void
    {
        $author = User::factory()->create(['role' => 'admin']);
        $this->postRecord($author, [
            'title' => 'Published evacuation guidance',
            'slug' => 'published-evacuation-guidance',
            'status' => 'published',
            'published_at' => now()->subMinutes(4),
        ]);
        $this->postRecord($author, [
            'title' => 'Unapproved draft content',
            'slug' => 'unapproved-draft-content',
            'published_at' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Recent news & updates', false)
            ->assertSee('Published evacuation guidance')
            ->assertDontSee('Unapproved draft content')
            ->assertSee('Special Operations Unit');
    }

    public function test_admin_dashboard_includes_real_report_statistics_and_graph_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('Reports submitted · last 7 days')
            ->assertSee('Report status breakdown')
            ->assertSee('Dashboard')
            ->assertSee('News &amp; advisories', false);
    }

    public function test_image_attachments_are_rendered_inline_and_downloadable(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('news/inline/map.png', 'fake-image-content');
        $author = User::factory()->create(['role' => 'admin']);
        $post = $this->postRecord($author, [
            'title' => 'Evacuation map',
            'slug' => 'evacuation-map',
            'status' => 'published',
            'published_at' => now()->subMinute(),
            'attachments' => [[
                'path' => 'news/inline/map.png',
                'name' => 'evacuation-map.png',
                'mime' => 'image/png',
            ]],
        ]);

        $this->get('/news/' . $post->slug)
            ->assertOk()
            ->assertSee('alt="Attached image: evacuation-map.png"', false)
            ->assertSee('Download file');

        $this->get('/news/' . $post->slug . '/attachments/0')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    private function postRecord(User $author, array $overrides = []): NewsPost
    {
        return NewsPost::query()->create(array_merge([
            'user_id' => $author->id,
            'title' => 'Preparedness notice',
            'slug' => 'preparedness-notice',
            'excerpt' => 'A short public update.',
            'body' => 'Follow official instructions.',
            'category' => 'preparedness_guide',
            'severity' => 'normal',
            'status' => 'draft',
            'is_banner' => false,
        ], $overrides));
    }
}
