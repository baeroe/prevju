<?php

namespace App\Http\Controllers\Api;

use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $projects = QueryBuilder::for(Project::class)
            ->allowedFilters(AllowedFilter::partial('name'))
            ->allowedIncludes('sites')
            ->allowedSorts('name', 'created_at', 'updated_at')
            ->defaultSort('-updated_at')
            ->paginate($this->perPage($request))
            ->withQueryString();

        return ProjectResource::collection($projects);
    }

    public function store(Request $request)
    {
        $project = (new Project)->applySettings($request->validate(Project::rules(creating: true)));
        $project->save();

        return ProjectResource::make($project->refresh());
    }

    /** Always with its sites, newest change first. */
    public function show(Project $project)
    {
        return ProjectResource::make($project->load('sites'));
    }

    public function update(Request $request, Project $project)
    {
        $project->applySettings($request->validate(Project::rules()))->save();

        return ProjectResource::make($project);
    }

    /** The sites stay, they become standalone again. */
    public function destroy(Project $project)
    {
        $project->delete();

        return response()->noContent();
    }
}
