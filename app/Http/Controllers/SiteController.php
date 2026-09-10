<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mime\MimeTypes;

class SiteController extends Controller
{
    public function show(Request $request, string $slug, string $path = '')
    {
        $site = Site::where('slug', $slug)->firstOrFail();

        if ($site->password && ! $request->session()->get("site.{$site->id}")) {
            return response()->view('site-password', ['site' => $site], 401);
        }

        $root = realpath($site->dir()) ?: abort(404);
        $file = realpath($root.'/'.($path ?: '.')) ?: abort(404);
        abort_unless($file === $root || str_starts_with($file, $root.DIRECTORY_SEPARATOR), 404);

        if (is_dir($file)) {
            if (! str_ends_with($request->getPathInfo(), '/')) {
                return redirect()->away($request->url().'/');
            }
            $file = $this->indexOf($file) ?? abort(404);
        }

        $ext = pathinfo($file, PATHINFO_EXTENSION);
        $mime = MimeTypes::getDefault()->getMimeTypes($ext)[0] ?? 'application/octet-stream';

        return response()->file($file, ['Content-Type' => $mime]);
    }

    public function unlock(Request $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();

        if (! Hash::check($request->input('password', ''), $site->password)) {
            return back()->withErrors(['password' => 'Falsches Passwort.']);
        }

        $request->session()->put("site.{$site->id}", true);

        return redirect()->away($site->url());
    }

    /** index.html, else the only/first .html in the folder. */
    private function indexOf(string $dir): ?string
    {
        if (is_file("$dir/index.html")) {
            return "$dir/index.html";
        }

        return File::glob("$dir/*.html")[0] ?? null;
    }
}
