<?php

namespace Tests\Feature;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private static function translation(string $key): \Closure
    {
        return fn (string $expected) => fn ($translations) => ($translations[$key] ?? null) === $expected;
    }

    public function test_default_is_english_whatever_the_browser_says(): void
    {
        $this->withHeader('Accept-Language', 'de-DE,de;q=0.9')->get('/en/login')
            ->assertSee('lang="en"', false)
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'en')->where('translations', self::translation('login.submit')('Log in')));
    }

    public function test_prefix_picks_language_and_is_remembered(): void
    {
        $this->get('/de/login')->assertSee('lang="de"', false)->assertCookie('locale', 'de')
            ->assertInertia(fn (Assert $page) => $page->where('locale', 'de')->where('translations', self::translation('login.submit')('Anmelden')));
    }

    public function test_unprefixed_admin_urls_redirect(): void
    {
        $this->get('/')->assertRedirect('/en/sites');
        $this->get('/login')->assertRedirect('/en/login');
        $this->withCookie('locale', 'de')->get('/sites/3')->assertRedirect('/de/sites/3');
    }

    public function test_unknown_locale_prefix_is_404(): void
    {
        $this->get('/fr/sites')->assertNotFound();
    }

    public function test_unknown_cookie_locale_is_ignored(): void
    {
        $this->withCookie('locale', '../../etc')->get('/sites')->assertRedirect('/en/sites');
    }

    public function test_redirects_keep_the_language(): void
    {
        $this->get('/de/sites')->assertRedirect('/de/login');
    }

    public function test_client_pages_follow_cookie_and_switch(): void
    {
        Site::create(['name' => 'Test', 'slug' => 'loc123', 'password' => Hash::make('x')]);

        $this->get('/s/loc123/')->assertSee('Open draft');
        $this->from('/s/loc123/')->get('/locale/de')->assertRedirect('/s/loc123/')->assertCookie('locale', 'de');
        $this->withCookie('locale', 'de')->get('/s/loc123/')->assertSee('Entwurf öffnen')->assertSee('lang="de"', false);
    }

    public function test_locale_switch_rejects_unknown_locale(): void
    {
        $this->get('/locale/fr')->assertNotFound();
    }

    public function test_locale_switch_redirects_back_only_inside_the_app(): void
    {
        $this->withHeader('Referer', 'https://evil.example/phish')->get('/locale/en')->assertRedirect(url('/'));
    }

    public function test_both_languages_have_the_same_keys(): void
    {
        $keys = fn (string $l) => array_keys(json_decode(file_get_contents(lang_path("{$l}.json")), true, flags: JSON_THROW_ON_ERROR));

        $this->assertSame([], array_values(array_diff($keys('en'), $keys('de'))), 'missing in de.json');
        $this->assertSame([], array_values(array_diff($keys('de'), $keys('en'))), 'missing in en.json');
    }

    public function test_every_key_used_in_code_exists(): void
    {
        $en = json_decode(file_get_contents(lang_path('en.json')), true);
        $files = [
            ...glob(base_path('resources/js/{,*/,*/*/}*.{ts,tsx}'), GLOB_BRACE),
            ...glob(base_path('app/{,*/,*/*/,*/*/*/}*.php'), GLOB_BRACE),
            ...glob(resource_path('views/*.blade.php')),
        ];
        $code = collect($files)->map(fn ($f) => file_get_contents($f))->implode("\n");

        preg_match_all("/\\b(t|tn|__)\\(\\s*'([a-z_]+(?:\\.[a-z_]+)+)'/", $code, $m, PREG_SET_ORDER);
        $used = collect($m)->flatMap(fn ($match) => $match[1] === 'tn' ? ["{$match[2]}.one", "{$match[2]}.other"] : [$match[2]])->unique();

        $this->assertGreaterThan(50, $used->count(), 'texts should go through t()/__()');
        $this->assertSame([], $used->diff(array_keys($en))->values()->all());
    }

    public function test_login_keeps_the_language_it_was_done_in(): void
    {
        User::factory()->create(['email' => 'a@b.de', 'password' => Hash::make('secret123')]);

        $this->get('/en/sites/1')->assertRedirect('/en/login');
        $this->post('/de/login', ['email' => 'a@b.de', 'password' => 'secret123'])->assertRedirect(url('/de/sites/1'));
    }
}
