<?php

namespace App\Domain\SelectionProcess\Services;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Exceptions\ProjectsAreNotInComplianceException;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;

class ReviewAssigmentService
{
    public function assignIndicated(Collection $projects, Collection $reviewers): void
    {
        foreach ($projects as $project) {
            $indicatedName = data_get(
                $project->original_content,
                'indicacao_de_especialista_do_corpo_docente_do_ppghis_para_avaliacao_do_projeto_de_pesquisa',
            ) ?? $project->indication;
            $indicatedName = strtolower((string) $indicatedName);

            if ($indicatedName) {
                $reviewer = $reviewers->first(function ($user) use ($indicatedName) {
                    return str_contains(strtolower($user->name), $indicatedName)
                        || str_contains($indicatedName, strtolower($user->name));
                });

                if ($reviewer) {
                    ReviewAssignment::updateOrCreate(
                        [
                            'project_id' => $project->id,
                            'user_id' => $reviewer->id,
                        ],
                        [
                            'chosen_by_candidate' => true,
                        ]
                    );
                }
            }
        }
    }

    public function assignRemainder(Collection $projects, Collection $reviewers): void
    {
        if ($projects->isEmpty() || $reviewers->isEmpty()) {
            return;
        }

        $reviewerIds = $reviewers->pluck('id');
        $projectIds = $projects->pluck('id');

        // Mapear contagem atual de atribuições para cada avaliador no contexto destes projetos
        $assignmentCounts = ReviewAssignment::whereIn('project_id', $projectIds)
            ->whereIn('user_id', $reviewerIds)
            ->selectRaw('user_id, count(*) as aggregate')
            ->groupBy('user_id')
            ->pluck('aggregate', 'user_id')
            ->toArray();

        // Inicializar avaliadores que não têm atribuições com 0
        foreach ($reviewerIds as $id) {
            $assignmentCounts[$id] ??= 0;
        }

        // Mapear quem já está em qual projeto para evitar duplicatas e saber quantos faltam
        $existingAssignments = ReviewAssignment::whereIn('project_id', $projectIds)->get();
        $existingAssignmentsByProject = $existingAssignments->groupBy('project_id');

        foreach ($projects as $project) {
            $currentAssignments = $existingAssignmentsByProject->get($project->id, collect());
            $currentReviewerIds = $currentAssignments->pluck('user_id')->toArray();
            $hasChosenByCandidate = $currentAssignments->contains('chosen_by_candidate', true);

            $needed = 3 - count($currentReviewerIds);

            if ($needed <= 0) {
                continue;
            }

            // Avaliadores disponíveis (que não estão no projeto atual)
            $availableForThisProject = $reviewers->reject(fn ($r) => in_array($r->id, $currentReviewerIds));

            for ($i = 0; $i < $needed; $i++) {
                if ($availableForThisProject->isEmpty()) {
                    break;
                }

                // Escolher o avaliador com menor carga (shuffle para tie-break aleatório)
                $chosenReviewer = $availableForThisProject
                    ->shuffle()
                    ->sortBy(fn ($r) => $assignmentCounts[$r->id])
                    ->first();

                if (! $chosenReviewer) {
                    break;
                }

                $isChosenByCandidate = ! $hasChosenByCandidate;

                ReviewAssignment::create([
                    'project_id' => $project->id,
                    'user_id' => $chosenReviewer->id,
                    'chosen_by_candidate' => $isChosenByCandidate,
                ]);

                if ($isChosenByCandidate) {
                    $hasChosenByCandidate = true;
                }

                // Atualizar carga localmente para manter o equilíbrio nos próximos passos
                $assignmentCounts[$chosenReviewer->id]++;

                // Remover o escolhido da lista de disponíveis para ESTE projeto
                $availableForThisProject = $availableForThisProject->reject(fn ($r) => $r->id === $chosenReviewer->id);
            }
        }
    }

    public function complete(SelectionProcess $selection): void
    {
        $approvedProjects = $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::APPROVED);
        $isFullyAssigned = $approvedProjects->count() === $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::APPROVED)
            ->has('reviewAssignments', '>=', 3)
            ->count();

        if (! $isFullyAssigned) {
            throw new ProjectsAreNotInComplianceException(
                'Todos os projetos devem ter pelo menos 3 avaliadores atribuídos.'
            );
        }

        $selection->update([
            'phase' => SelectionProcessPhases::REVIEW,
        ]);

        $projects = $selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::APPROVED)
            ->has('reviewAssignments', '>=', 3)
            ->get();

        $projects->each(function (Project $project) {
            $project->update([
                'stage', ProjectStage::REVIEW,
            ]);
        });
    }
}
