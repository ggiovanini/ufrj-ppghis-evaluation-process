<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Services\ImportProjectsArchiveService;
use App\Domain\Projects\Services\ImportProjectsService;
use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Services\ReviewAssigmentService;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectsImportRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Http\Resources\UserResource;
use App\Imports\ProjectsExcelImport;
use App\Models\Project;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class SelectionProcessController extends Controller
{
    public function index() {}

    public function create()
    {
        Gate::authorize('projects.manage');

        return Inertia::render('selection/Create', [
            'reviewForms' => ReviewForm::where('active', true)->get(),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('projects.manage');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'review_form_id' => ['required', 'exists:review_forms,id'],
        ]);

        $selection = SelectionProcess::create($validated);

        return to_route('selection.show', $selection);
    }

    public function show(Request $request, SelectionProcess $selection)
    {
        Gate::authorize('projects.import');

        if ($selection->projects->count() <= 0) {
            return to_route('selection.prepare', $selection);
        }

        $projects = $selection->projects();

        if (in_array($selection->phase, [
            SelectionProcessPhases::DISTRIBUTION,
            SelectionProcessPhases::REVIEW,
            SelectionProcessPhases::WRITTEN_EXAM,
            SelectionProcessPhases::COMMITTEE,
            SelectionProcessPhases::RESULTS,
            SelectionProcessPhases::FINISHED,
        ], true)) {
            $projects->where('homologation_status', ProjectHomologationStatus::APPROVED);
        }

        if ($selection->phase === SelectionProcessPhases::WRITTEN_EXAM) {
            $projects = $projects
                ->where('modality', ProjectModality::MASTER)
                ->whereNotNull('review_score')
                ->whereNot('stage', ProjectStage::REJECTED);
        }

        $projects = $projects
            ->with(['reviewAssignments.user'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('candidate_name', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%");
                });
            })
            ->when($request->sort, function ($query, $sort) use ($request) {
                $query->orderBy($sort, $request->direction ?? 'asc');
            }, function ($query) {
                $query->orderBy('candidate_name');
            })
            ->paginate()
            ->withQueryString();

        $reviewers = User::role('reviewer')
            ->withCount(['reviewAssignments as assigned_count' => function ($query) use ($selection) {
                $query->whereIn('project_id', $selection->projects()->pluck('id'));
            }])
            ->withCount(['reviewAssignments as completed_count' => function ($query) use ($selection) {
                $query->whereIn('project_id', $selection->projects()->pluck('id'))
                    ->whereHas('review', function ($query) {
                        $query->where('status', ReviewStatus::SUBMITTED);
                    });
            }])
            ->get();

        return Inertia::render('selection/Show', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'reviewers' => UserResource::collection($reviewers)->map(fn ($user) => array_merge($user->resolve(), [
                'assigned_count' => $user->assigned_count,
                'completed_count' => $user->completed_count,
            ])),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'stats' => FrontIntegrationService::selectionProcessStats($selection),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
            'homologationPendingProjects' => $selection->projects()
                ->where('homologation_status', ProjectHomologationStatus::PENDING)
                ->count(),
        ]);
    }

    public function updateHomologation(Request $request, SelectionProcess $selection, Project $project): RedirectResponse
    {
        Gate::authorize('projects.manage');

        abort_unless($project->selection_process_id === $selection->id, 403);
        abort_unless($selection->phase === SelectionProcessPhases::HOMOLOGATION, 409);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected'],
            'reason' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
        ]);

        $project->update([
            'homologation_status' => $validated['status'],
            'homologation_reason' => $validated['status'] === ProjectHomologationStatus::REJECTED->value
                ? $validated['reason']
                : null,
        ]);

        return back();
    }

    public function approveAllAndFinalize(SelectionProcess $selection): RedirectResponse
    {
        Gate::authorize('projects.manage');

        abort_unless($selection->phase === SelectionProcessPhases::HOMOLOGATION, 409);

        $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::PENDING)
            ->update([
                'homologation_status' => ProjectHomologationStatus::APPROVED,
                'homologation_reason' => null,
            ]);

        $selection->projects()
            ->where('stage', ProjectStage::IMPORTED)
            ->update([
                'stage' => ProjectStage::HOMOLOGATED,
            ]);

        return $this->finalize($selection);
    }

    public function prepare(SelectionProcess $selection)
    {
        Gate::authorize('projects.import');

        return Inertia::render('selection/Import', [
            'selection' => new SelectionProcessResource($selection),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
            'inboxFiles' => collect(Storage::disk('local')->files('inbox'))
                ->filter(fn (string $path): bool => strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'zip')
                ->map(fn (string $path): string => basename($path))
                ->sort()
                ->values()
                ->all(),
        ]);
    }

    public function import(ProjectsImportRequest $request, SelectionProcess $selection): RedirectResponse
    {
        $archivePath = null;
        $archiveService = null;
        $filesIndex = null;
        $spreadsheet = $request->file('file');

        if ($request->filled('inbox_file')) {
            $archiveService = new ImportProjectsArchiveService;
            $archivePath = $archiveService->extract(
                $request->string('inbox_file')->toString(),
                $selection,
            );
            $filesIndex = $archiveService->filesIndex();
            $spreadsheet = $archivePath;
        }

        $projects = Excel::toCollection(new ProjectsExcelImport, $spreadsheet);
        $importProjectsService = new ImportProjectsService(
            $selection,
            new Project,
            $filesIndex,
        );
        $createdCount = $importProjectsService->import($projects);

        $selection->update(['phase' => SelectionProcessPhases::HOMOLOGATION]);

        if ($archivePath !== null) {
            $archiveService?->moveToOutbox(
                $request->string('inbox_file')->toString(),
            );
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$createdCount} Projetos importados com sucesso.",
        ]);

        return to_route('selection.show', [
            'selection' => $selection->id,
        ]);
    }

    public function edit(SelectionProcess $selection) {}

    public function update(SelectionProcess $selection) {}

    public function destroy(SelectionProcess $selection) {}

    public function finalize(SelectionProcess $selection): RedirectResponse
    {
        Gate::authorize('projects.manage');

        try {
            if ($selection->phase === SelectionProcessPhases::HOMOLOGATION) {
                $pendingProjects = $selection->projects()
                    ->where('homologation_status', ProjectHomologationStatus::PENDING)
                    ->exists();

                if ($pendingProjects) {
                    throw new ProjectsAreNotInComplianceException(
                        'Todos os projetos precisam ser aprovados ou desaprovados antes de avançar.'
                    );
                }

                if (! $selection->projects()->where('homologation_status', ProjectHomologationStatus::APPROVED)->exists()) {
                    throw new ProjectsAreNotInComplianceException(
                        'É necessário aprovar pelo menos um projeto para avançar.'
                    );
                }

                $selection->update(['phase' => SelectionProcessPhases::DISTRIBUTION]);

                return to_route('selection.show', $selection);
            }

            if (in_array($selection->phase, [
                SelectionProcessPhases::IMPORT,
                SelectionProcessPhases::DISTRIBUTION,
            ], true)) {
                $approvedProjects = $selection->projects()
                    ->where('homologation_status', ProjectHomologationStatus::APPROVED)
                    ->get();
                $reviewers = User::role('reviewer')->get();
                (new ReviewAssigmentService)->assignIndicated($approvedProjects, $reviewers);

                $reviewAssigmentService = new ReviewAssigmentService;
                $reviewAssigmentService->complete($selection);

                $reviewService = new ReviewService($selection);
                $reviewService->createForSelectionProcess();
                $reviewService->notifyReviewers();

                $selection->update([
                    'phase' => SelectionProcessPhases::REVIEW,
                ]);

                return to_route('selection.show', $selection);
            }

            if ($selection->phase === SelectionProcessPhases::REVIEW) {
                // Check if all reviews are submitted
                $pendingReviews = $selection->projects()->whereHas('reviewAssignments.review', function ($query) {
                    $query->where('status', '!=', ReviewStatus::SUBMITTED);
                })->exists();

                if ($pendingReviews) {
                    throw new ProjectsAreNotInComplianceException(
                        'Ainda existem avaliações pendentes.'
                    );
                }

                $selection->update([
                    'phase' => SelectionProcessPhases::WRITTEN_EXAM,
                ]);

                return to_route('selection.show', $selection);
            }

            throw new ProjectsAreNotInComplianceException(
                'Essa alteração não é permitida no momento.'
            );
        } catch (ProjectsAreNotInComplianceException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function returnToHomologation(SelectionProcess $selection): RedirectResponse
    {
        Gate::authorize('projects.manage');

        abort_unless($selection->phase === SelectionProcessPhases::DISTRIBUTION, 409);

        $selection->update([
            'phase' => SelectionProcessPhases::HOMOLOGATION,
        ]);

        $selection->projects()->update([
            'stage' => ProjectStage::IMPORTED,
        ]);

        return to_route('selection.show', $selection);
    }
}
