<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function index()
    {
        return Inertia::render('sites/index', [
            'projects' => Project::with('sites')->latest('updated_at')->get()->map->adminCard(),
            'sites' => Site::whereNull('project_id')->latest('updated_at')->get()->map->adminCard(),
        ]);
    }

    public function show(Site $site)
    {
        return Inertia::render('sites/show', [
            'site' => [...$site->adminCard(), 'files' => $site->fileList()],
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'project_id' => 'nullable|integer|exists:projects,id',
        ]);
        $site = Site::create([...$data, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null]);

        return redirect()->route('sites.show', $site);
    }

    public function update(Request $request, Site $site)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
            'project_id' => 'sometimes|nullable|integer|exists:projects,id',
        ]);

        if (isset($data['name'])) {
            $site->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $site->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $site->password = null;
        }
        if (array_key_exists('project_id', $data)) {
            $site->project_id = $data['project_id'];
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
}
