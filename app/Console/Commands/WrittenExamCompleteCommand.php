<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Services\WrittenExamService;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('written:exam-complete {number?}')]
#[Description('Completa todas as avaliações escritas')]
class WrittenExamCompleteCommand extends Command
{
    public function handle(): void
    {
        $user = User::role(UserRoles::ADMIN->value)->first();
        $query = Project::where('stage', ProjectStage::WRITTEN_EXAM)
            ->whereNull('written_exam_score');

        $number = (int) $this->argument('number');
        if ($number > 0) {
            $query->limit($this->argument('number'));
        }
        $projectsInPhaseWrittenExams = $query->get();

        $writtenExamService = app(WrittenExamService::class);
        foreach ($projectsInPhaseWrittenExams as $projectInPhaseWrittenExam) {
            $writtenExamService->update($projectInPhaseWrittenExam, rand(500, 1000), $user);
        }
    }
}
