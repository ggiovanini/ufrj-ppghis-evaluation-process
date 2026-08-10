<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Services\CommitteeReviewService;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\User;
use Illuminate\Console\Command;

class CommitteeCompleteSameCommand extends Command
{
    protected $signature = 'committee:complete-same';

    protected $description = 'Completa algumas avaliações dos comitês';

    public function handle(): void
    {
        $user = User::role(UserRoles::ADMIN->value)->firstOrFail();
        $projectsInCommittee = Project::where('stage', ProjectStage::COMMITTEE)
            ->whereNull('committee_score')
            ->inRandomOrder()
            ->limit(10)
            ->get();

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
