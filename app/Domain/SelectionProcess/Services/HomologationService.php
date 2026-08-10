<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\SelectionProcess;

class HomologationService
{
    public function finalize(SelectionProcess $selection): void
    {
        $pendingProjects = $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::PENDING)
            ->exists();

        if ($pendingProjects) {
            throw new ProjectsAreNotInComplianceException(
                'Todos os projetos precisam ser aprovados ou desaprovados antes de avançar.'
            );
        }

        if (! $selection->projects()->where('homologation_status', ProjectHomologationStatus::APPROVED)->exists()) {
            throw new ProjectsAreNotInComplianceException(
                'É necessário aprovar pelo menos um projeto para avançar.'
            );
        }

        $projects = $selection->projects;
        foreach ($projects as $project) {
            if ($project->homologation_status === ProjectHomologationStatus::APPROVED) {
                $project->update(['stage' => ProjectStage::HOMOLOGATED]);
            } else {
                $project->update([
                    'rejected_on_stage' => ProjectStage::IMPORTED,
                    'stage' => ProjectStage::REJECTED,
                ]);
            }
        }

        $selection->update(['phase' => SelectionProcessPhases::DISTRIBUTION]);
    }
}
