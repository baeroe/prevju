<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Visit;
use App\Support\VisitRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\Mime\MimeTypes;

class SiteController extends Controller
{
    public function show(Request $request, string $slug, string $path = '')
    {
        $site = Site::where('slug', $slug)->firstOrFail();

        if (! $site->isOpenFor($request)) {
            return response()->view('site-password', [
                'name' => $site->name,
                'action' => route('site.unlock', $site->slug),
                'intro' => __('client.intro_site'),
                'button' => __('client.open_site'),
            ], 401);
        }

        return $this->serve($request, $site, $path, track: true);
    }

    public function preview(Request $request, string $signature, string $slug, string $path = '')
    {
        $site = Site::where('slug', $slug)->firstOrFail();
        abort_unless(hash_equals($site->previewSignature(), $signature), 404);

        return $this->serve($request, $site, $path);
    }

    /** $track: count HTML pages as visits; assets and the admin's preview iframes are not counted. */
    private function serve(Request $request, Site $site, string $path, bool $track = false)
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
        if ($track && in_array(strtolower($ext), ['html', 'htm'])) {
            VisitRecorder::record($request, Visit::VIEW, site: $site, path: '/'.$path);
        }
        $mime = MimeTypes::getDefault()->getMimeTypes($ext)[0] ?? 'application/octet-stream';

        return response()->file($file, ['Content-Type' => $mime]);
    }

    public function unlock(Request $request, string $slug)
    {
        $site = Site::where('slug', $slug)->firstOrFail();

        if (! Hash::check($request->input('password', ''), $site->password)) {
            VisitRecorder::record($request, Visit::UNLOCK_FAILED, site: $site);

            return back()->withErrors(['password' => __('password.wrong')]);
        }

        $request->session()->put("site.{$site->id}", true);
        VisitRecorder::record($request, Visit::UNLOCK, site: $site);

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
