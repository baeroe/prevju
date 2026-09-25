<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function index()
    {
        return Inertia::render('sites/index', [
            'sites' => Site::latest('updated_at')->get()->map($this->card(...)),
        ]);
    }

    public function show(Site $site)
    {
        return Inertia::render('sites/show', [
            'site' => [...$this->card($site), 'files' => $site->fileList()],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'password' => 'nullable|string|max:255']);
        $site = Site::create([...$data, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null]);

        return redirect("/sites/{$site->id}");
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
        ]);

        if (isset($data['name'])) {
            $site->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $site->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $site->password = null;
        }
        $site->save();

        return back();
    }

    public function destroy(Site $site)
    {
        $site->delete();

        return back();
    }

    /** One file per request so the client can show progress and stay under post_max_size. */
    public function upload(Request $request, Site $site)
    {
        $request->validate(['file' => 'required|file|max:102400', 'path' => 'required|string|max:1024']);
        $site->addFile($request->file('file'), $request->input('path'));

        return response()->noContent();
    }

    public function deleteFile(Request $request, Site $site)
    {
        $site->deleteFile($request->validate(['path' => 'required|string'])['path']);

        return back();
    }

    private function card(Site $site): array
    {
        return [...$site->summary(), 'preview_url' => $site->previewUrl()];
    }
}
