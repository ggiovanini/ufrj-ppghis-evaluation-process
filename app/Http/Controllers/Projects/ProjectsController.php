<?php

namespace App\Http\Controllers\Projects;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Types\ProjectScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Domain\Shared\Types\UserRoles;
use App\Exports\HomologationReportExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\SelectionProcessResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
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

        return Inertia::render('projects/List', [
            'selection' => new SelectionProcessResource($selection),
            'projects' => ProjectResource::collection($projects),
            'reviewers' => UserResource::collection($reviewers)->map(fn ($user) => array_merge($user->resolve(), [
                'assigned_count' => $user->assigned_count,
                'completed_count' => $user->completed_count,
            ])),
            'filters' => $request->only(['search', 'sort', 'direction']),
            'stats' => FrontIntegrationService::selectionProcessStats($selection),
            'phases' => FrontIntegrationService::selectionProcessPhases(),
        ]);
    }

    public function show(SelectionProcess $selection, Project $project): Response
    {
        abort_unless(Gate::any(['projects.view', 'projects.manage', 'review.view-own', 'committee.evaluate']), 403);

        $user = auth()->user();
        $canManageHomologation = $selection->phase === SelectionProcessPhases::HOMOLOGATION
            && $user->can('projects.manage');
        if (! $user->hasRole(UserRoles::ADMIN->value)
            && ! $canManageHomologation
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

    public function update(Request $request, SelectionProcess $selection, Project $project)
    {
        Gate::authorize('projects.manage');

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $validated = $request->validate([
            'written_exam_score' => ['required', 'string'],
        ]);

        $project->update([
            'written_exam_score' => (new ProjectScore($validated['written_exam_score']))->value(),
        ]);

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

        $selection->projects()->delete();

        return back();
    }
}
