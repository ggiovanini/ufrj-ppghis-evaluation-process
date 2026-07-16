<?php

namespace App\Http\Controllers\Projects;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsController extends Controller
{
    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('project.view');

        return Inertia::render('projects/List', [
            'selection' => $selection,
            'projects' => $selection->projects,
        ]);
    }

    public function show(SelectionProcess $selection, Project $project) {}

    public function edit(SelectionProcess $selection, Project $project) {}

    public function update(SelectionProcess $selection, Project $project) {}

    public function destroy(SelectionProcess $selection, Project $project) {}
}
