<?php

namespace App\Domain\Review\Services;

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Notifications\ReviewAssignmentsNotification;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;

class ReviewService
{
    protected ?SelectionProcess $selectionProcess = null;

    public function __construct(?SelectionProcess $selectionProcess = null)
    {
        if ($selectionProcess) {
            $this->selectionProcess = $selectionProcess;
        }
    }

    public function createReviewAssignment(Project $project, User $reviewer, bool $chosen_by_candidate = false): void
    {
        $project->reviewAssignments()->updateOrCreate([
            'user_id' => $reviewer->id,
        ], [
            'chosen_by_candidate' => $chosen_by_candidate,
        ]);

        if ($this->selectionProcess && $this->selectionProcess->phase !== SelectionProcessPhases::IMPORT) {
            $projectAssignmentCount = $project->reviewAssignments()->count();
            if ($projectAssignmentCount >= 3) {
                $project->update([
                    'stage' => ProjectStage::REVIEW,
                ]);
            }
        }
    }

    public function createForProject(Project $project): void
    {
        $reviewAssignments = $project->reviewAssignments;
        $reviewAssignments->each(function (ReviewAssignment $reviewAssignment) {
            $reviewAssignment->review()->create([
                'review_form_id' => $this->selectionProcess->reviewForm->id,
                'status' => ReviewStatus::PENDENT,
            ]);
        });
    }

    public function createForSelectionProcess(?SelectionProcess $selectionProcess = null): void
    {
        if ($selectionProcess) {
            $this->selectionProcess = $selectionProcess;
        }

        $this->selectionProcess->projects->each(function (Project $project) {
            $this->createForProject($project);
        });
    }

    public function notifyReviewers(bool $onlyPending = false): void
    {
        if (! $this->selectionProcess) {
            return;
        }

        $reviewers = User::whereHas('reviewAssignments.project', function ($query) {
            $query->where('selection_process_id', $this->selectionProcess->id);
        })
            ->when($onlyPending, function ($query) {
                $query->whereHas('reviewAssignments', function ($query) {
                    $query->whereIn('project_id', $this->selectionProcess->projects()->pluck('id'))
                        ->whereHas('review', function ($query) {
                            $query->where('status', ReviewStatus::PENDENT);
                        });
                });
            })
            ->get();

        foreach ($reviewers as $reviewer) {
            $this->notifyReviewer($reviewer);
        }
    }

    public function notifyReviewer(User $reviewer): void
    {
        if (! $this->selectionProcess) {
            return;
        }

        $projects = Project::whereHas('reviewAssignments', function ($query) use ($reviewer) {
            $query->where('user_id', $reviewer->id);
        })
            ->where('selection_process_id', $this->selectionProcess->id)
            ->whereHas('reviewAssignments.review', function ($query) {
                $query->where('status', ReviewStatus::PENDENT);
            })
            ->get();

        if ($projects->isNotEmpty()) {
            $reviewer->notify(new ReviewAssignmentsNotification($projects));
        }
    }

    public function returnDistributionStep(ProjectService $projectService): void
    {
        if (! $this->selectionProcess) {
            return;
        }

        $projectService->deleteAllReviewAssignment($this->selectionProcess);
    }

    public function update(ReviewAssignment $assignment, array $validated, array $answers = []): void
    {
        if (! $this->selectionProcess) {
            return;
        }

        $assignment->review()->updateOrCreate(
            ['review_assignment_id' => $assignment->id],
            [
                'score' => $validated['score'] ?? ReviewScore::PENDENT->value,
                'comments' => $validated['comments'] ?? null,
                'questions' => $validated['questions'] ?? null,
                'answers' => $answers,
                'status' => $validated['status'],
                'review_form_id' => $this->selectionProcess->review_form_id,
                'submitted_at' => $validated['status'] === ReviewStatus::SUBMITTED->value ? now() : null,
            ]
        );

        $this->completeReviewVerify($assignment->project);
    }

    public function completeReviewVerify(Project $project): void
    {
        if ($project->reviewAssignments->every(fn ($a) => $a->review?->status === ReviewStatus::SUBMITTED)) {
            $projectService = new ProjectService($project);
            $projectService->calculeReviewStepScore();
            $projectService->startCommitteeReview();
            $projectService->startWrittenExam();
        }
    }

    public function finalize(?SelectionProcess $selection = null): void
    {
        if ($selection) {
            $this->selectionProcess = $selection;
        }

        $pendingReviews = $this->selectionProcess->projects()->whereHas('reviewAssignments.review', function ($query) {
            $query->where('status', '!=', ReviewStatus::SUBMITTED);
        })->exists();

        if ($pendingReviews) {
            throw new ProjectsAreNotInComplianceException(
                'Ainda existem avaliações pendentes.'
            );
        }

        $this->selectionProcess->update([
            'phase' => SelectionProcessPhases::WRITTEN_EXAM,
        ]);

        $projects = $this->selectionProcess->projects()
            ->where('stage', ProjectStage::WRITTEN_EXAM)->get();

        foreach ($projects as $project) {
            $project->writtenExam()->updateOrCreate([], [
                'score' => null,
                'passed' => false,
                'user_id' => auth()->id(),
                'recorded_at' => null,
            ]);
        }
    }
}
