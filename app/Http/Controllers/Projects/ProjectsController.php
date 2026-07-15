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
    public function import(ProjectsImportRequest $request, SelectionProcess $selection): RedirectResponse
    {
        $projects = Excel::toCollection(new ProjectsExcelImport, $request->file('file'));
        $importProjectsService = new ImportProjectsService(
            $selection,
            new Project(),
        );
        $createdCount = $importProjectsService->import($projects);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$createdCount} Projetos importados com sucesso."
        ]);

        return to_route('selection.projects', [
            'selection' => $selection->id
        ]);
    }

    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('project.view');

        return Inertia::render('projects/List', [
            'selection' => $selection,
            'projects' => $selection->projects,
        ]);
    }
}
