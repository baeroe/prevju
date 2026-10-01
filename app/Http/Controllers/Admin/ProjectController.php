<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProjectController extends Controller
{
    public function show(Project $project)
    {
        return Inertia::render('projects/show', ['project' => $project->adminCard()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255', 'password' => 'nullable|string|max:255']);
        $project = Project::create([...$data, 'password' => filled($data['password'] ?? null) ? Hash::make($data['password']) : null]);

        return redirect("/projects/{$project->id}");
    }

    public function update(Request $request, Project $project)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'password' => 'nullable|string|max:255',
            'clear_password' => 'boolean',
        ]);

        if (isset($data['name'])) {
            $project->name = $data['name'];
        }
        if (filled($data['password'] ?? null)) {
            $project->password = Hash::make($data['password']);
        } elseif ($data['clear_password'] ?? false) {
            $project->password = null;
        }
        $project->save();

        return back();
    }

    /** The sites stay, they become standalone again (nullOnDelete). */
    public function destroy(Project $project)
    {
        $project->delete();

        return back();
    }
}
