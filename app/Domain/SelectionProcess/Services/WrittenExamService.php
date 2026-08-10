<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
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

        $project->update([
            'written_exam_score' => $score,
        ]);

        $project->writtenExam()->updateOrCreate([], [
            'score' => $score,
            'passed' => (new ProjectService($project))->passesMinimumScore($score),
            'user_id' => $user->id,
            'recorded_at' => now(),
        ]);
    }

    public function finalize(SelectionProcess $selectionProcess): void
    {
        $pendentProjects = $selectionProcess->projects()
            ->where('modality', ProjectModality::MASTER)
            ->where('stage', ProjectStage::WRITTEN_EXAM)
            ->whereDoesntHave('writtenExam')->get();

        if ($pendentProjects->isNotEmpty()) {
            throw new ProjectsAreNotInComplianceException('Existem projetos sem notas submetidas.');
        }

        $selectionProcess->update([
            'phase' => SelectionProcessPhases::COMMITTEE,
        ]);

        $projects = $selectionProcess->projects()
            ->where('modality', ProjectModality::MASTER)
            ->where('stage', ProjectStage::WRITTEN_EXAM)
            ->whereHas('writtenExam')->get();

        foreach ($projects as $project) {
            $writtenExam = $project->writtenExam;
            if ($writtenExam->passed) {
                $project->update([
                    'stage' => ProjectStage::COMMITTEE,
                ]);
                $this->committeeReviewService->createCommitteeReview($project);
            } else {
                $project->reject();
            }
        }
    }
}
