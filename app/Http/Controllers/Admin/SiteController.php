<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Site;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SiteController extends Controller
{
    public function index()
    {
        return Inertia::render('sites/index', [
            'sites' => Site::withVisitStats()->latest('updated_at')->get()->map->adminCard(),
            // targets for the sidebar and the move menu on each card
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Site $site)
    {
        return Inertia::render('sites/show', [
            'site' => [...$site->adminCard(), 'files' => $site->fileList()],
            'visits' => $site->visits()->latest()->limit(100)->get()->map->summary(),
            'visit_days' => config('prevju.visits_retention_days'),
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $site = (new Site)->applySettings($request->validate(Site::rules(creating: true)));
        $site->save();

        return redirect()->route('sites.show', $site);
    }

    public function update(Request $request, Site $site)
    {
        $site->applySettings($request->validate(Site::rules()))->save();

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
