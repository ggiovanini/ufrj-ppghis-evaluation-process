<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Domain\Projects\Services\ImportProjectsService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectsImportRequest;
use App\Imports\ProjectsExcelImport;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SelectionProcessController extends Controller
{
    public function index() {}

    public function store() {}

    public function show(SelectionProcess $selection) {}

    public function import(ProjectsImportRequest $request, SelectionProcess $selection): RedirectResponse
    {
        $projects = Excel::toCollection(new ProjectsExcelImport, $request->file('file'));
        $importProjectsService = new ImportProjectsService(
            $selection,
            new Project,
        );
        $createdCount = $importProjectsService->import($projects);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$createdCount} Projetos importados com sucesso.",
        ]);

        return to_route('selection.projects.index', [
            'selection' => $selection->id,
        ]);
    }

    public function edit(SelectionProcess $selection) {}

    public function update(SelectionProcess $selection) {}

    public function destroy(SelectionProcess $selection) {}
}
