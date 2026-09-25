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

        // the logged-in admin opens protected sites without typing the password
        if ($site->password && ! $request->user() && ! $request->session()->get("site.{$site->id}")) {
            return response()->view('site-password', ['site' => $site], 401);
        }

        return $this->serve($request, $site, $path);
    }

    public function preview(Request $request, string $signature, string $slug, string $path = '')
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        abort_unless(hash_equals($site->previewSignature(), $signature), 404);

        return $this->serve($request, $site, $path);
    }

    private function serve(Request $request, Site $site, string $path)
    {
        $root = realpath($site->dir()) ?: abort(404);
        $file = realpath($root.'/'.($path ?: '.'));
        // SPA fallback: unknown paths without extension (client-side routes) get the site's index
        if (! $file && pathinfo($path, PATHINFO_EXTENSION) === '') {
            $file = $this->indexOf($root);
        }
        $file ?: abort(404);
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
