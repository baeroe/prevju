<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        return Inertia::render('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate(['email' => 'required|email', 'password' => 'required']);

        if (! Auth::attempt($credentials, remember: true)) {
            return back()->withErrors(['email' => __('login.failed')])->onlyInput('email');
        }

        $request->session()->regenerate();

        // back to the page that asked for the login, but in the language the login was done in
        $intended = $request->session()->pull('url.intended', route('projects.index'));

        return redirect(preg_replace('#^('.preg_quote(url('/'), '#').')/(en|de)(?=/|$)#', '$1/'.app()->getLocale(), $intended));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
