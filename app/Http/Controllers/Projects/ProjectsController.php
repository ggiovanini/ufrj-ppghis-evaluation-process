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
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
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
        $canManageHomologation = $selection->phase === SelectionProcessPhases::HOMOLOGATION
            && $user->can('projects.manage');
        if (! $user->hasRole(UserRoles::ADMIN->value)
            && ! $canManageHomologation
            && ! $canEvaluateCommittee
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
        ]);

        return Inertia::render('projects/Show', [
            'selection' => new SelectionProcessResource($selection),
            'project' => (new ProjectResource($project))->resolve(),
            'reviewScoreOptions' => FrontIntegrationService::reviewScoreOptions(),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
        ]);
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
        Gate::authorize('projects.manage');

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $validated = $request->validate([
            'written_exam_score' => ['required', 'string'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 401);

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
                    ]);
                    $project->update(['committee_score' => null]);
                });

            return back();
        }

        // $selection->projects()->delete();

        return back();
    }
}
