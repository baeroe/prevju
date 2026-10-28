<?php

namespace App\Support;

use App\Models\Project;
use App\Models\Site;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Lottery;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Records what clients open, without consent banner: no full IP, no cookie, no fingerprint that
 * outlives the day. The visitor hash only tells "same browser today", never who it was.
 */
class VisitRecorder
{
    // link previews in chat apps fetch the page as soon as the link is sent, that is not the client opening it
    private const BOTS = '/bot|crawl|spider|slurp|facebookexternalhit|whatsapp|telegram|slack|discord|skype|preview|headless|curl|wget|python|httpclient|go-http|okhttp/i';

    public static function record(Request $request, string $kind, ?Site $site = null, ?Project $project = null, ?string $path = null): void
    {
        $days = config('prevju.visits_retention_days');
        $agent = (string) $request->userAgent();

        // the admin looking at their own drafts is not a visit, nor are link checkers (HEAD) and browser prefetches
        $prefetch = str_contains((string) ($request->header('Sec-Purpose') ?? $request->header('Purpose')), 'prefetch');
        if ($days <= 0 || $request->user() || $request->isMethod('HEAD') || $prefetch || $agent === '' || preg_match(self::BOTS, $agent)) {
            return;
        }

        Visit::create([
            'site_id' => $site?->id,
            'project_id' => $project?->id,
            'kind' => $kind,
            'path' => $path,
            'visitor' => self::visitor($request, $agent),
            ...self::parse($agent),
            'referrer' => self::referrer($request),
        ]);

        // no cron in the container: old visits are cleaned up along the way
        Lottery::odds(1, 100)
            ->winner(fn () => Visit::where('created_at', '<', now()->subDays($days))->delete())
            ->choose();
    }

    /** Same IP range and browser on the same day give the same hash; the salt is random and dropped after two days. */
    private static function visitor(Request $request, string $agent): string
    {
        $salt = Cache::remember('visits.salt.'.now()->toDateString(), now()->addDays(2), fn () => Str::random(32));

        return substr(hash_hmac('sha256', IpUtils::anonymize((string) $request->ip()).'|'.$agent, $salt), 0, 16);
    }

    /** @return array{device: string, browser: ?string, os: ?string} */
    public static function parse(string $agent): array
    {
        $browser = match (true) {
            str_contains($agent, 'Edg') => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $agent) => 'Opera',
            str_contains($agent, 'SamsungBrowser') => 'Samsung Internet',
            (bool) preg_match('/Firefox|FxiOS/', $agent) => 'Firefox',
            (bool) preg_match('/Chrome|CriOS/', $agent) => 'Chrome',
            str_contains($agent, 'Safari') => 'Safari',
            default => null,
        };

        $os = match (true) {
            (bool) preg_match('/iPhone|iPad|iPod/', $agent) => 'iOS',
            str_contains($agent, 'Android') => 'Android',
            str_contains($agent, 'Windows') => 'Windows',
            str_contains($agent, 'CrOS') => 'ChromeOS',
            str_contains($agent, 'Macintosh') => 'macOS',
            str_contains($agent, 'Linux') => 'Linux',
            default => null,
        };

        $device = match (true) {
            (bool) preg_match('/iPad|Tablet/', $agent), $os === 'Android' && ! str_contains($agent, 'Mobile') => 'tablet',
            (bool) preg_match('/Mobi|iPhone|iPod/', $agent) => 'mobile',
            default => 'desktop',
        };

        return ['device' => $device, 'browser' => $browser, 'os' => $os];
    }

    /** Host of the page the link was clicked on, e.g. mail.google.com; own pages don't count. */
    private static function referrer(Request $request): ?string
    {
        $host = parse_url((string) $request->headers->get('referer'), PHP_URL_HOST);

        return $host && $host !== $request->getHost() ? Str::limit($host, 250, '') : null;
    }
}
