<?php

namespace App\Http\Controllers\Projects;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectScore;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Services\CommitteeReviewService;
use App\Domain\SelectionProcess\Services\WrittenExamService;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Domain\Shared\Types\UserRoles;
use App\Exports\CommitteeReportExport;
use App\Exports\DistributionReportExport;
use App\Exports\FinalResultReportExport;
use App\Exports\HomologationReportExport;
use App\Exports\ReviewReportExport;
use App\Exports\WrittenExamReportExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Projects\ProjectDocumentUploadRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\ProjectDocumentVersion;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ProjectsController extends Controller
{
    public function index(Request $request, SelectionProcess $selection): Response
    {
        Gate::authorize('projects.view');

        $projects = $selection->projects()
            ->with(['reviewAssignments.user', 'committeeEvaluation', 'finalResults'])
            ->when(ProjectStage::tryFrom($request->string('status')->toString()), function ($query, ProjectStage $status) {
                $query->where('stage', $status);
            })
            ->when(ProjectModality::tryFrom($request->string('modality')->toString()), function ($query, ProjectModality $modality) {
                $query->where('modality', $modality);
            })
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

        return Inertia::render('projects/List', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'reviewers' => UserResource::collection($reviewers)->map(fn ($user) => array_merge($user->resolve(), [
                'assigned_count' => $user->assigned_count,
                'completed_count' => $user->completed_count,
            ])),
            'filters' => $request->only(['search', 'sort', 'direction', 'status', 'modality']),
            'stats' => FrontIntegrationService::selectionProcessStats($selection),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
        ]);
    }

    public function show(SelectionProcess $selection, Project $project): Response
    {
        abort_unless(Gate::any(['projects.view', 'projects.manage', 'review.view-own', 'committee.evaluate']), 403);

        $user = auth()->user();
        $canEvaluateCommittee = $user->can('committee.evaluate')
            && (($user->hasRole(UserRoles::MASTER_COMMITTEE->value) && $project->modality === ProjectModality::MASTER)
                || ($user->hasRole(UserRoles::DOCTORATE_COMMITTEE->value) && $project->modality === ProjectModality::DOCTORATE));
        $canEvaluateWrittenExam = $user->can('committee.evaluate')
            && $user->hasRole(UserRoles::MASTER_COMMITTEE->value)
            && $selection->phase === SelectionProcessPhases::WRITTEN_EXAM
            && $project->modality === ProjectModality::MASTER
            && $project->stage === ProjectStage::WRITTEN_EXAM;
        $canManageHomologation = $selection->phase === SelectionProcessPhases::HOMOLOGATION
            && $user->can('projects.manage');
        if (! $user->hasRole(UserRoles::ADMIN->value)
            && ! $canManageHomologation
            && ! $canEvaluateCommittee
            && ! $canEvaluateWrittenExam
            && ! $project->reviewAssignments()->where('user_id', $user->id)->exists()
        ) {
            abort(403);
        }

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $project->load([
            'reviewAssignments.user',
            'reviewAssignments.review',
            'reviewAssignments.review.reviewForm',
            'reviewAssignments.review.reviewAssignment.project',
            'writtenExam',
            'committeeEvaluation',
            'finalResults',
            'documentVersions.user',
        ]);

        return Inertia::render('projects/Show', [
            'selection' => new SelectionProcessResource($selection),
            'project' => (new ProjectResource($project))->resolve(),
            'reviewScoreOptions' => FrontIntegrationService::reviewScoreOptions(),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
        ]);
    }

    public function uploadDocument(ProjectDocumentUploadRequest $request, SelectionProcess $selection, Project $project): RedirectResponse
    {
        abort_unless($project->selection_process_id === $selection->id, 403);

        $validated = $request->validated();
        $documents = data_get($project->content, 'documents', []);
        $rawDocumentIndex = $validated['document_index'] ?? null;
        $documentIndex = $rawDocumentIndex !== null && $rawDocumentIndex !== ''
            ? (int) $rawDocumentIndex
            : null;
        $oldDocument = $documentIndex !== null ? ($documents[$documentIndex] ?? null) : null;
        $label = $validated['label'];
        $version = ProjectDocumentVersion::where('project_id', $project->id)->where('label', $label)->max('version') + 1;

        if (is_array($oldDocument) && isset($oldDocument['path'])) {
            ProjectDocumentVersion::create([
                'project_id' => $project->id,
                'label' => $oldDocument['label'] ?? $label,
                'name' => $oldDocument['name'] ?? $oldDocument['filename'] ?? 'Arquivo',
                'filename' => $oldDocument['filename'] ?? $oldDocument['name'] ?? 'arquivo',
                'path' => $oldDocument['path'],
                'version' => $version,
                'action' => 'replaced',
            ]);
            $version++;
        }

        $file = $request->file('file');
        $filename = Str::uuid()->toString().'.'.$file->extension();
        $path = $file->storeAs('projects/'.$project->id.'/documents', $filename, 'public');
        ProjectDocumentVersion::create([
            'project_id' => $project->id,
            'user_id' => $request->user()->id,
            'label' => $label,
            'name' => $file->getClientOriginalName(),
            'filename' => $filename,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'version' => $version,
            'action' => is_array($oldDocument) ? 'replacement' : 'upload',
        ]);

        $newDocument = ['label' => $label, 'name' => $file->getClientOriginalName(), 'filename' => $filename, 'ext' => $file->extension(), 'size' => $file->getSize(), 'path' => $path, 'url' => Storage::disk('public')->url($path)];
        if ($documentIndex !== null && array_key_exists($documentIndex, $documents)) {
            $documents[$documentIndex] = $newDocument;
        } else {
            $documents[] = $newDocument;
        }
        $content = $project->content ?? [];
        $content['documents'] = array_values($documents);
        $project->update(['content' => $content]);

        return back();
    }

    public function edit(SelectionProcess $selection, Project $project) {}

    public function homologationReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new HomologationReportExport($selection),
            'relatorio-homologacao-'.$selection->id.'.xlsx',
        );
    }

    public function distributionReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new DistributionReportExport($selection),
            'relatorio-distribuicao-'.$selection->id.'.xlsx',
        );
    }

    public function reviewReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new ReviewReportExport($selection),
            'relatorio-avaliacao-'.$selection->id.'.xlsx',
        );
    }

    public function writtenExamReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new WrittenExamReportExport($selection),
            'relatorio-prova-escrita-'.$selection->id.'.xlsx',
        );
    }

    public function committeeReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new CommitteeReportExport($selection),
            'relatorio-comite-'.$selection->id.'.xlsx',
        );
    }

    public function finalResultReport(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        return Excel::download(
            new FinalResultReportExport($selection),
            'relatorio-resultado-final-'.$selection->id.'.xlsx',
        );
    }

    public function update(
        Request $request,
        WrittenExamService $writtenExamService,
        SelectionProcess $selection,
        Project $project,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        $isMasterCommittee = $user->can('committee.evaluate')
            && $user->hasRole(UserRoles::MASTER_COMMITTEE->value);

        abort_unless($user->can('projects.manage') || $isMasterCommittee, 403);

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        if (! $user->can('projects.manage')) {
            abort_unless($selection->phase === SelectionProcessPhases::WRITTEN_EXAM, 409);
            abort_unless($project->stage === ProjectStage::WRITTEN_EXAM, 409);
            abort_unless($project->modality === ProjectModality::MASTER, 403);
        }

        $validated = $request->validate([
            'written_exam_score' => ['required', 'string'],
        ]);

        $writtenExamService->update(
            $project,
            (new ProjectScore($validated['written_exam_score']))->value(),
            $user,
        );

        return back();
    }

    public function updateCommitteeScore(
        Request $request,
        CommitteeReviewService $committeeReviewService,
        SelectionProcess $selection,
        Project $project,
    ): RedirectResponse {
        abort_unless(Gate::any(['projects.manage', 'committee.evaluate']), 403);

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $user = $request->user();
        abort_unless($user instanceof User, 401);

        if (! $user->can('projects.manage')) {
            abort_unless($selection->phase === SelectionProcessPhases::COMMITTEE, 409);
            abort_unless($project->stage === ProjectStage::COMMITTEE, 409);
            abort_unless(
                ($user->hasRole(UserRoles::MASTER_COMMITTEE->value) && $project->modality === ProjectModality::MASTER)
                || ($user->hasRole(UserRoles::DOCTORATE_COMMITTEE->value) && $project->modality === ProjectModality::DOCTORATE),
                403,
            );
        }

        $validated = $request->validate([
            'committee_score' => ['required', 'string'],
            'comments' => ['required', 'string', 'max:1000'],
        ]);

        $committeeReviewService->update(
            $project,
            (new ProjectScore($validated['committee_score']))->value(),
            $validated['comments'],
            $user,
        );

        return back();
    }

    public function destroy(SelectionProcess $selection, Project $project)
    {
        Gate::authorize('projects.manage');

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $project->delete();

        return back();
    }

    public function destroyAll(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $user_id = auth()->id();

        if ($selection->phase === SelectionProcessPhases::DISTRIBUTION) {
            $selection->projects()
                ->where('stage', ProjectStage::HOMOLOGATED)
                ->each(function (Project $project) {
                    $project->reviewAssignments()->delete();
                    $project->update(['stage', ProjectStage::IMPORTED]);
                });

            return back();
        }

        if ($selection->phase === SelectionProcessPhases::REVIEW) {
            $selection->projects()
                ->where('stage', ProjectStage::REVIEW)
                ->each(function (Project $project) {
                    $project->reviewAssignments()->delete();
                    $project->update(['stage', ProjectStage::HOMOLOGATED]);
                });

            return back();
        }

        if ($selection->phase === SelectionProcessPhases::WRITTEN_EXAM) {
            $selection->projects()->where('stage', ProjectStage::WRITTEN_EXAM)
                ->each(function ($project) use ($user_id) {
                    $project->writtenExam()->updateOrCreate([], [
                        'score' => null,
                        'passed' => false,
                        'user_id' => $user_id,
                        'recorded_at' => null,
                    ]);
                    $project->update(['written_exam_score' => null]);
                });

            return back();
        }

        if ($selection->phase === SelectionProcessPhases::COMMITTEE) {
            $selection->projects()->where('stage', ProjectStage::COMMITTEE)
                ->each(function ($project) use ($user_id) {
                    $project->committeeEvaluation()->updateOrCreate([], [
                        'score' => null,
                        'comments' => null,
                        'user_id' => $user_id,
                        'submitted_at' => null,
                        'passed' => null,
                    ]);
                    $project->update(['committee_score' => null]);
                });

            return back();
        }

        // $selection->projects()->delete();

        return back();
    }
}
