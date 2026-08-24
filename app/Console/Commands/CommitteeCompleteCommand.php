<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Services\CommitteeReviewService;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Command;

#[Signature('committee:complete {number?}')]
#[Description('Completa todas as avaliações dos comitês')]
class CommitteeCompleteCommand extends Command
{
    public function handle(): void
    {
        $user = User::role(UserRoles::ADMIN->value)->firstOrFail();
        $query = Project::where('stage', ProjectStage::COMMITTEE)
            ->whereNull('committee_score');
        $number = (int) $this->argument('number');
        if ($number > 0) {
            $query->limit($this->argument('number'));
        }
        $projectsInCommittee = $query->get();

        $committeeReviewService = app(CommitteeReviewService::class);
        foreach ($projectsInCommittee as $projectInCommittee) {
            $committeeReviewService->update(
                $projectInCommittee,
                rand(500, 1000),
                fake()->sentence(),
                $user,
            );
        }
    }
}
