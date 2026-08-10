<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Services\WrittenExamService;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;

class WrittenExamCompleteSameCommand extends Command
{
    protected $signature = 'written:exam-complete-same';

    protected $description = 'Completa algumas das avaliações escritas';

    public function handle(): void
    {
        $user = User::role(UserRoles::ADMIN->value)->first();
        $projectsInPhaseWrittenExams = Project::where('stage', ProjectStage::WRITTEN_EXAM)
            ->whereNull('written_exam_score')
            ->inRandomOrder()
            ->limit(10)
            ->get();

        $writtenExamService = app(WrittenExamService::class);
        foreach ($projectsInPhaseWrittenExams as $projectInPhaseWrittenExam) {
            $writtenExamService->update($projectInPhaseWrittenExam, rand(500, 1000), $user);
        }
    }
}
