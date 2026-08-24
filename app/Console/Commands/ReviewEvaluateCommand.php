<?php

namespace App\Console\Commands;

use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\ReviewAssignment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('review:evaluate {number?}')]
#[Description('Finaliza a avaliação de todos os projetos')]
class ReviewEvaluateCommand extends Command
{
    public function handle(): void
    {
        $number = (int) $this->argument('number');
        $query = ReviewAssignment::query()->inRandomOrder();
        if ($number > 0) {
            $query->limit($number);
        }
        $assignments = $query->get();
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
    }
}
