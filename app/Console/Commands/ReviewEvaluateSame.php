<?php

namespace App\Console\Commands;

use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('review:evaluate-same')]
#[Description('Avalia alguns projetos com avaliações randomizadas')]
class ReviewEvaluateSame extends Command
{
    public function handle(): void
    {
        $projectsWithAssignments = Project::with('reviewAssignments')
            ->inRandomOrder()
            ->limit(5)
            ->get();

        $projectsWithAssignments->each(function (Project $project) {
            $assignments = $project->reviewAssignments;
            $assignments->each(function ($assignment) {
                $selectionProcess = $assignment?->project?->selectionProcess;
                if (! $selectionProcess) {
                    return;
                }

                $reviewService = new ReviewService($selectionProcess);
                $validated = [
                    'score' => collect([3, 5, 8, 10])->random(),
                    'comments' => fake()->text(),
                    'questions' => fake()->text(),
                    'status' => ReviewStatus::SUBMITTED,
                ];
                $reviewService->update($assignment, $validated, [
                    '1' => 'Sim',
                    '2' => 'Não',
                    '3' => 'Em parte',
                    '4' => 'Sim',
                    '5' => 'Não',
                    '6' => 'Em parte',
                    '7' => 'Sim',
                    '8' => fake()->text(),
                ]);
            });
        });
    }
}
