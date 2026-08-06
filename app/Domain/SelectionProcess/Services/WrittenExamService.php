<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Projects\Types\ProjectModality;
use App\Models\Project;
use App\Models\User;

class WrittenExamService
{
    public function __construct(
        protected CommitteeReviewService $committeeReviewService,
    ) {}

    public function update(Project $project, int $score, User $user): void
    {
        if ($project->modality !== ProjectModality::MASTER) {
            $this->committeeReviewService->createCommitteeReview($project);

            return;
        }

        $project->writtenExam()->updateOrCreate([
            'project_id' => $user->id,
        ], [
            'score' => $score,
            'user_id' => $user->id,
        ]);
    }
}
