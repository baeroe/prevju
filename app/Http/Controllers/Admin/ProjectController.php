<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Visit;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function index()
    {
        return Inertia::render('projects/index', [
            'projects' => Project::with(['sites' => fn ($q) => $q->withVisitStats()])->latest('updated_at')->get()->map->adminCard(),
        ]);
    }

    public function show(Project $project)
    {
        return Inertia::render('projects/show', [
            'project' => $project->load(['sites' => fn ($q) => $q->withVisitStats()])->adminCard(),
            'visits' => Visit::forProject($project)->latest()->limit(100)->get()->map->summary(),
            'visit_days' => config('prevju.visits_retention_days'),
            // for the project field of the new-site dialog
            'projects' => Project::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $project = (new Project)->applySettings($request->validate(Project::rules(creating: true)));
        $project->save();

        return redirect()->route('projects.show', $project);
    }

    public function update(Request $request, Project $project)
    {
        $project->applySettings($request->validate(Project::rules()))->save();

        return back();
    }

    /** The sites stay, they become standalone again (nullOnDelete). */
    public function destroy(Project $project)
    {
        $project->delete();

        return back();
    }
}
