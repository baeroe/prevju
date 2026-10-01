<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

/**
 * Admin pages take the language from their /en|/de prefix and remember it in a cookie.
 * Client pages keep their URLs and use that cookie. Default: English.
 */
class SetLocale
{
    public const LOCALES = ['en', 'de'];

    public function handle(Request $request, Closure $next)
    {
        $prefix = $request->route('locale');
        $cookie = $request->cookie('locale');
        $locale = in_array($prefix, self::LOCALES, true) ? $prefix : (in_array($cookie, self::LOCALES, true) ? $cookie : 'en');

        app()->setLocale($locale);
        URL::defaults(['locale' => $locale]);
        // controllers don't take the prefix as an argument
        $request->route()?->forgetParameter('locale');

        $response = $next($request);
        if ($prefix && $cookie !== $locale) {
            $response->headers->setCookie(cookie()->forever('locale', $locale));
        }

        return $response;
    }
}
