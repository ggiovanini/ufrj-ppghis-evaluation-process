<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Committee\Types\FinalStatus;
use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Support\Facades\DB;

class ResultService
{
    public function create(Project $project): void
    {
        $this->update($project);
    }

    public function update(Project $project): void
    {
        $weightedScores = $project->modality === ProjectModality::MASTER
            ? [
                [$project->review_score, 2],
                [$project->written_exam_score, 4],
                [$project->committee_score, 4],
            ]
            : [
                [$project->review_score, 4],
                [$project->committee_score, 6],
            ];

        if (collect($weightedScores)->contains(fn (array $score) => $score[0] === null)) {
            throw new ProjectsAreNotInComplianceException('Existem projetos sem todas as notas necessárias para calcular o resultado.');
        }

        $finalScore = (int) round(collect($weightedScores)->sum(
            fn (array $score): float => $score[0] * $score[1]
        ) / 10);
        $passed = (new ProjectService($project))->passesMinimumScore($finalScore);

        $project->update([
            'final_score' => $finalScore,
        ]);

        $project->finalResults()->updateOrCreate([], [
            'review_average' => $project->review_score / 100,
            'written_exam_score' => $project->written_exam_score,
            'committee_score' => $project->committee_score,
            'final_score' => $finalScore,
            'passed' => $passed,
            'status' => $passed ? FinalStatus::APPROVED : FinalStatus::REJECTED,
        ]);
    }

    public function finalize(SelectionProcess $selectionProcess): void
    {
        $projects = $selectionProcess->projects()
            ->whereHas('committeeEvaluation')
            ->get();

        DB::transaction(function () use ($projects, $selectionProcess): void {
            foreach ($projects as $project) {
                $this->update($project);
            }

            $selectionProcess->update([
                'phase' => SelectionProcessPhases::FINISHED,
            ]);
        });
    }

    public function recalculateAll(SelectionProcess $selectionProcess): void
    {
        $projects = $selectionProcess->projects()
            ->whereHas('committeeEvaluation')
            ->get();

        DB::transaction(function () use ($projects): void {
            foreach ($projects as $project) {
                $this->update($project);
            }
        });
    }
}
