<?php

namespace App\Http\Controllers\SelectionProcess;

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Review\Services\ReviewService;
use App\Domain\SelectionProcess\Services\ReviewAssigmentService;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewAssignmentController extends Controller
{
    public function store(Request $request, SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
            'chosen_by_candidate' => ['boolean'],
        ]);

        $project = Project::findOrFail($validated['project_id']);

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        if ($selection->phase === SelectionProcessPhases::DISTRIBUTION) {
            abort_unless($project->homologation_status === ProjectHomologationStatus::APPROVED, 422);
        }

        $user = User::findOrFail($validated['user_id']);
        if (! $user->hasRole('reviewer')) {
            abort(403);
        }

        $reviewService = new ReviewService($selection);
        $reviewService->createReviewAssignment(
            $project,
            $user,
            $validated['chosen_by_candidate'] ?? false
        );

        return back();
    }

    public function autoAssign(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $projects = $selection->projects()->get();
        if ($selection->phase === SelectionProcessPhases::DISTRIBUTION) {
            $projects = $projects->where('homologation_status', ProjectHomologationStatus::APPROVED);
        }
        $reviewers = User::role('reviewer')->get();

        $reviewAssigmentService = new ReviewAssigmentService;
        $reviewAssigmentService->assignIndicated($projects, $reviewers);

        return back();
    }

    public function autoAssignComplete(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $projects = $selection->projects()->get();
        if ($selection->phase === SelectionProcessPhases::DISTRIBUTION) {
            $projects = $projects->where('homologation_status', ProjectHomologationStatus::APPROVED);
        }
        $reviewers = User::role('reviewer')->get();

        $reviewAssigmentService = new ReviewAssigmentService;
        $reviewAssigmentService->assignIndicated($projects, $reviewers);
        $reviewAssigmentService->assignRemainder($projects, $reviewers);

        return back();
    }

    public function destroyAll(SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $projectService = new ProjectService;
        $projectService->deleteAllReviewAssignment($selection);

        return back();
    }

    public function destroyForProject(SelectionProcess $selection, Project $project)
    {
        Gate::authorize('projects.manage');

        if ($project->selection_process_id !== $selection->id) {
            abort(403);
        }

        $projectService = new ProjectService($project);
        $projectService->deleteReviewAssignment();

        return back();
    }

    public function destroy(Request $request, SelectionProcess $selection)
    {
        Gate::authorize('projects.manage');

        $validated = $request->validate([
            'project_id' => ['required', 'exists:projects,id'],
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $project = $selection->projects()->findOrFail($validated['project_id']);
        $reviewAssignment = $project->reviewAssignments()->where('user_id', $validated['user_id'])->firstOrFail();
        $projectService = new ProjectService($project);
        $projectService->deleteReviewAssignment($reviewAssignment);

        return back();
    }
}
