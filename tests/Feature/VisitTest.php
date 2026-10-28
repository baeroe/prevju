<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Site;
use App\Models\User;
use App\Models\Visit;
use App\Support\VisitRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Lottery;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class VisitTest extends TestCase
{
    use RefreshDatabase;

    private const IPHONE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1';

    private const MAC_CHROME = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36';

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = Site::create(['name' => 'Entwurf', 'slug' => 'abc123']);
        $this->site->writeFiles(['index.html' => '<h1>hi</h1>', 'about.html' => '<h1>about</h1>', 'css/style.css' => 'h1{}']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/sites/abc123'));
        parent::tearDown();
    }

    public function test_html_pages_are_counted_assets_are_not(): void
    {
        $headers = ['User-Agent' => self::IPHONE, 'Referer' => 'https://mail.google.com/mail/u/0/'];
        $this->get('/s/abc123/index.html', $headers)->assertOk();
        $this->get('/s/abc123/about.html', $headers)->assertOk();
        $this->get('/s/abc123/css/style.css', $headers)->assertOk();

        $this->assertSame(['/index.html', '/about.html'], Visit::orderBy('id')->pluck('path')->all());
        $visit = Visit::first();
        $this->assertSame([Visit::VIEW, 'mobile', 'Safari', 'iOS', 'mail.google.com'], [$visit->kind, $visit->device, $visit->browser, $visit->os, $visit->referrer]);
        $this->assertSame(16, strlen($visit->visitor));
    }

    public function test_admin_previews_and_link_unfurlers_are_not_counted(): void
    {
        $this->get("/p/{$this->site->previewSignature()}/abc123/index.html", ['User-Agent' => self::MAC_CHROME])->assertOk();
        $this->get('/s/abc123/index.html', ['User-Agent' => 'WhatsApp/2.24.1.6 A'])->assertOk();
        $this->get('/s/abc123/index.html', ['User-Agent' => 'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)'])->assertOk();
        $this->withHeaders(['User-Agent' => ''])->get('/s/abc123/index.html')->assertOk();
        $this->get('/s/abc123/index.html', ['User-Agent' => self::MAC_CHROME, 'Sec-Purpose' => 'prefetch'])->assertOk();
        $this->call('HEAD', '/s/abc123/index.html', server: ['HTTP_USER_AGENT' => self::MAC_CHROME])->assertOk();
        $this->actingAs(User::factory()->create())->get('/s/abc123/index.html', ['User-Agent' => self::MAC_CHROME])->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_visitor_hash_is_stable_per_browser_and_hides_the_ip(): void
    {
        $this->get('/s/abc123/index.html', ['User-Agent' => self::IPHONE]);
        $this->get('/s/abc123/about.html', ['User-Agent' => self::IPHONE]);
        $this->get('/s/abc123/index.html', ['User-Agent' => self::MAC_CHROME]);

        $visitors = Visit::orderBy('id')->pluck('visitor');
        $this->assertSame($visitors[0], $visitors[1]);
        $this->assertNotSame($visitors[0], $visitors[2]);
        $this->assertStringNotContainsString('127.0.0', Visit::all()->toJson());
    }

    public function test_password_attempts_are_recorded(): void
    {
        $this->site->update(['password' => Hash::make('geheim')]);

        $this->withHeaders(['User-Agent' => self::MAC_CHROME]);
        $this->get('/s/abc123/index.html')->assertUnauthorized();
        $this->post('/s/abc123', ['password' => 'falsch']);
        $this->post('/s/abc123', ['password' => 'geheim'])->assertRedirect();
        $this->get('/s/abc123/index.html')->assertOk();

        $this->assertSame([Visit::UNLOCK_FAILED, Visit::UNLOCK, Visit::VIEW], Visit::orderBy('id')->pluck('kind')->all());
    }

    public function test_project_page_and_its_sites_add_up(): void
    {
        $project = Project::create(['name' => 'Relaunch']);
        $this->site->update(['project_id' => $project->id]);

        $this->get("/project/{$project->slug}", ['User-Agent' => self::MAC_CHROME])->assertOk();
        $this->get('/s/abc123/index.html', ['User-Agent' => self::MAC_CHROME])->assertOk();

        $this->assertSame(1, Visit::where('project_id', $project->id)->count());
        $this->assertSame(2, $project->visitStats()['view_count']);
        $this->assertSame(1, $this->site->visitStats()['view_count']);
    }

    public function test_client_page_does_not_show_stats(): void
    {
        $project = Project::create(['name' => 'Relaunch']);
        $this->site->update(['project_id' => $project->id]);

        $this->get("/project/{$project->slug}")->assertInertia(fn (Assert $page) => $page->missing('sites.0.view_count')->missing('sites.0.last_viewed_at'));
    }

    public function test_admin_sees_stats_and_visit_list(): void
    {
        $this->get('/s/abc123/index.html', ['User-Agent' => self::IPHONE]);
        $this->actingAs(User::factory()->create());

        $this->get('/en/sites')->assertInertia(fn (Assert $page) => $page->where('sites.0.view_count', 1)->whereNot('sites.0.last_viewed_at', null));
        $this->get("/en/sites/{$this->site->id}")->assertInertia(fn (Assert $page) => $page
            ->has('visits', 1)
            ->where('visits.0.device', 'mobile')
            ->missing('visits.0.ip'));
    }

    public function test_tracking_can_be_turned_off(): void
    {
        config(['prevju.visits_retention_days' => 0]);
        $this->get('/s/abc123/index.html', ['User-Agent' => self::IPHONE])->assertOk();

        $this->assertSame(0, Visit::count());
    }

    public function test_old_visits_are_pruned(): void
    {
        Lottery::alwaysWin();
        $old = Visit::create(['site_id' => $this->site->id, 'kind' => Visit::VIEW, 'visitor' => 'x', 'device' => 'desktop']);
        $old->forceFill(['created_at' => now()->subDays(91)])->save();

        $this->get('/s/abc123/index.html', ['User-Agent' => self::IPHONE]);

        $this->assertModelMissing($old);
        $this->assertSame(1, Visit::count());
        Lottery::determineResultNormally();
    }

    public function test_deleting_a_site_deletes_its_visits(): void
    {
        $this->get('/s/abc123/index.html', ['User-Agent' => self::IPHONE]);
        $this->site->delete();

        $this->assertSame(0, Visit::count());
    }

    public function test_user_agents_are_parsed(): void
    {
        $this->assertSame(['device' => 'desktop', 'browser' => 'Chrome', 'os' => 'macOS'], VisitRecorder::parse(self::MAC_CHROME));
        $this->assertSame(['device' => 'tablet', 'browser' => 'Chrome', 'os' => 'Android'], VisitRecorder::parse('Mozilla/5.0 (Linux; Android 14; SM-X710) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36'));
        $this->assertSame(['device' => 'desktop', 'browser' => 'Edge', 'os' => 'Windows'], VisitRecorder::parse('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36 Edg/130.0.0.0'));
        $this->assertSame(['device' => 'mobile', 'browser' => 'Firefox', 'os' => 'Android'], VisitRecorder::parse('Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0'));
    }
}
