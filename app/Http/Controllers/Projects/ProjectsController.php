<?php

namespace App\Http\Controllers\Projects;

use App\Domain\Projects\Services\ImportProjectsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectsImportRequest;
use App\Imports\ProjectsExcelImport;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

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

    public function show(SelectionProcess $selection, Project $project)
    {

    }

    public function edit(SelectionProcess $selection, Project $project)
    {

    }

    public function update(SelectionProcess $selection, Project $project)
    {

    }

    public function destroy(SelectionProcess $selection, Project $project)
    {

    }
}
