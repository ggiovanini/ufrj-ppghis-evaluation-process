<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;

class CommitteeReviewService
{
    public function createCommitteeReview(Project $project): void
    {
        $project->update([
            'committee_score' => null,
        ]);

        $project->committeeEvaluation()->updateOrCreate([], [
            'score' => null,
            'passed' => null,
            'comments' => null,
            'user_id' => null,
            'submitted_at' => null,
        ]);
    }

    public function update(Project $project, int $score, string $comments, User $user): void
    {
        $project->update([
            'committee_score' => $score,
        ]);

        $project->committeeEvaluation()->updateOrCreate([], [
            'score' => $score,
            'passed' => (new ProjectService($project))->passesMinimumScore($score),
            'comments' => $comments,
            'user_id' => $user->id,
            'submitted_at' => now(),
        ]);
    }

    public function finalize(SelectionProcess $selectionProcess): void
    {
        $pendentProjects = $selectionProcess->projects()
            ->where('stage', ProjectStage::COMMITTEE)
            ->whereHas('committeeEvaluation')
            ->whereNull('committee_score')
            ->get();

        if ($pendentProjects->isNotEmpty()) {
            throw new ProjectsAreNotInComplianceException('Existem projetos sem notas submetidas.');
        }

        $resultService = app(ResultService::class);

        $projects = $selectionProcess->projects()
            ->where('stage', ProjectStage::COMMITTEE)
            ->whereHas('committeeEvaluation')
            ->get();

        foreach ($projects as $project) {
            $committeeReview = $project->committeeEvaluation;
            $resultService->create($project);
            if ($committeeReview->passed) {
                $project->update(['stage' => ProjectStage::FINISHED]);
            } else {
                $project->reject();
            }
        }

        $selectionProcess->update(['phase' => SelectionProcessPhases::RESULTS]);
    }
}
