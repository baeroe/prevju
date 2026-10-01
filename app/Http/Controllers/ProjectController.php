<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

/** The client's page for a project: all its sites, newest change first. */
class ProjectController extends Controller
{
    public function show(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if (! $project->isOpenFor($request)) {
            return response()->view('site-password', [
                'name' => $project->name,
                'action' => route('project.unlock', $project->slug),
                'intro' => 'Entwürfe zur Ansicht',
                'button' => 'Entwürfe öffnen',
            ], 401);
        }

        return Inertia::render('projects/public', [
            'project' => ['name' => $project->name],
            'sites' => $project->sites->map(fn (Site $site) => [
                ...$site->summary(),
                // the signed preview URL skips the site password: only for sites this viewer may open anyway
                'preview_url' => $site->setRelation('project', $project)->isOpenFor($request) ? $site->previewUrl() : null,
            ]),
        ]);
    }

    public function unlock(Request $request, string $slug)
    {
        $project = Project::where('slug', $slug)->firstOrFail();

        if (! Hash::check($request->input('password', ''), $project->password)) {
            return back()->withErrors(['password' => 'Falsches Passwort.']);
        }

        $request->session()->put("project.{$project->id}", true);

        return redirect()->away($project->url());
    }
}
