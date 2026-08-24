<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Shared\Types\UserRoles;
use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SelectionProcessDocumentsController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        abort_unless($user?->hasRole(UserRoles::ADMIN->value), 403);

        $selection = $user->currentSelectionProcess;

        abort_if($selection === null, 404);

        $projectModels = $selection->projects()
            ->orderBy('candidate_name')
            ->get(['id', 'candidate_name', 'register_id', 'title', 'content']);

        $usedDocuments = collect();
        $projectModels->each(function (Project $project) use ($usedDocuments): void {
            collect(data_get($project->content, 'documents', []))
                ->each(function (array $document) use ($project, $usedDocuments): void {
                    if (isset($document['path'])) {
                        $usedDocuments->put($document['path'], [
                            'id' => $project->id,
                            'candidate_name' => $project->candidate_name,
                        ]);
                    }
                });
        });

        $projects = $projectModels
            ->map(fn (Project $project): array => [
                'id' => $project->id,
                'candidate_name' => $project->candidate_name,
                'register_id' => $project->register_id,
                'title' => $project->title,
                'documents' => collect(data_get($project->content, 'documents', []))
                    ->map(fn (array $document): array => [
                        'name' => $document['name'] ?? $document['filename'] ?? 'Arquivo',
                        'label' => $document['label'] ?? null,
                        'filename' => $document['filename'] ?? null,
                        'path' => $document['path'] ?? null,
                        'url' => $document['url']
                            ?? (isset($document['path'])
                                ? Storage::disk('public')->url($document['path'])
                                : null),
                    ])
                    ->values()
                    ->all(),
            ])
            ->values();

        $publicDisk = Storage::disk('public');
        $storageDirectory = Str::slug($selection->name);
        $storageDocuments = collect($publicDisk->allFiles($storageDirectory))
            ->map(function (string $path) use ($publicDisk, $usedDocuments): array {
                $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                $project = $usedDocuments->get($path);

                return [
                    'name' => basename($path),
                    'path' => $path,
                    'url' => $publicDisk->url($path),
                    'is_used' => $project !== null,
                    'is_import_spreadsheet' => $extension === 'xlsx',
                    'project' => $project,
                ];
            })
            ->values();

        return Inertia::render('selection/Documents', [
            'selection' => [
                'data' => [
                    'id' => $selection->id,
                    'name' => $selection->name,
                    'description' => $selection->description,
                    'year' => $selection->year,
                    'phase' => $selection->phase->value,
                ],
            ],
            'projects' => $projects,
            'storageDocuments' => $storageDocuments,
            'phases' => FrontIntegrationService::selectionProcessPhases(),
        ]);
    }
}
