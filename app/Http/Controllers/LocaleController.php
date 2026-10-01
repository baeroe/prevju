<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;

/** Language switch on the client pages: remember the choice, back to where it was clicked. */
class LocaleController extends Controller
{
    public function __invoke(string $lang)
    {
        abort_unless(in_array($lang, SetLocale::LOCALES, true), 404);

        // never back to another host via a forged Referer
        $back = url()->previous();
        $target = str_starts_with($back, url('/').'/') || $back === url('/') ? $back : url('/');

        return redirect($target)->withCookie(cookie()->forever('locale', $lang));
    }
}
