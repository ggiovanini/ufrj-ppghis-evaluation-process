<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Services\ReviewService;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\SelectionProcess;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:sync-stages {--selection= : ID opcional do processo seletivo} {--dry-run : Simular correções sem gravar no banco}')]
#[Description('Sincroniza e corrige o estágio (stage) dos projetos de acordo com a fase atual e o status das avaliações')]
class ProjectSyncStagesCommand extends Command
{
    public function handle(ReviewService $reviewService): int
    {
        $selectionId = $this->option('selection');
        $dryRun = (bool) $this->option('dry-run');

        $query = SelectionProcess::query();
        if ($selectionId) {
            $query->where('id', $selectionId);
        }

        $selectionProcesses = $query->get();

        if ($selectionProcesses->isEmpty()) {
            $this->warn('Nenhum processo seletivo encontrado.');

            return self::SUCCESS;
        }

        $updatedCount = 0;
        $rows = [];

        foreach ($selectionProcesses as $selection) {
            $projects = $selection->projects()
                ->with(['reviewAssignments.review'])
                ->get();

            foreach ($projects as $project) {
                $oldStage = $project->stage;
                $newStage = null;
                $action = null;

                if ($selection->phase === SelectionProcessPhases::REVIEW) {
                    if ($project->homologation_status === ProjectHomologationStatus::APPROVED) {
                        $assignments = $project->reviewAssignments;
                        $has3Assignments = $assignments->count() >= 3;
                        $allSubmitted = $has3Assignments && $assignments->every(fn ($a) => $a->review?->status === ReviewStatus::SUBMITTED);

                        if ($allSubmitted) {
                            if (in_array($project->stage, [ProjectStage::IMPORTED, ProjectStage::HOMOLOGATED, ProjectStage::REVIEW], true)) {
                                if (! $dryRun) {
                                    $reviewService->completeReviewVerify($project);
                                    $project->refresh();
                                    $newStage = $project->stage;
                                    $action = "Avançado para {$newStage->value} via completeReviewVerify";
                                } else {
                                    $action = 'Simulado avanço via completeReviewVerify';
                                    $newStage = ProjectStage::REVIEW; // Indicação genérica
                                }
                                $updatedCount++;
                            }
                        } elseif (in_array($project->stage, [ProjectStage::IMPORTED, ProjectStage::HOMOLOGATED], true)) {
                            $newStage = ProjectStage::REVIEW;
                            if (! $dryRun) {
                                $project->update(['stage' => ProjectStage::REVIEW]);
                                $action = 'Corrigido para review';
                            } else {
                                $action = 'Simulado para review';
                            }
                            $updatedCount++;
                        }
                    }
                } elseif ($selection->phase === SelectionProcessPhases::DISTRIBUTION) {
                    if ($project->homologation_status === ProjectHomologationStatus::APPROVED && $project->stage === ProjectStage::IMPORTED) {
                        $newStage = ProjectStage::HOMOLOGATED;
                        if (! $dryRun) {
                            $project->update(['stage' => ProjectStage::HOMOLOGATED]);
                            $action = 'Corrigido para homologated';
                        } else {
                            $action = 'Simulado para homologated';
                        }
                        $updatedCount++;
                    }
                }

                if ($action !== null) {
                    $rows[] = [
                        $project->id,
                        $project->register_id ?? 'N/A',
                        $project->candidate_name ?? $project->title ?? 'N/A',
                        $selection->name,
                        $oldStage->value,
                        $newStage?->value ?? 'N/A',
                        $action,
                    ];
                }
            }
        }

        if (empty($rows)) {
            $this->info('Todos os projetos já estão com estágios coerentes.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Register ID', 'Candidato / Título', 'Processo Seletivo', 'Estágio Anterior', 'Novo Estágio', 'Ação'],
            $rows
        );

        if ($dryRun) {
            $this->info("Dry-run concluído: {$updatedCount} projeto(s) seriam atualizados.");
        } else {
            $this->info("Sucesso: {$updatedCount} projeto(s) sincronizados com sucesso.");
        }

        return self::SUCCESS;
    }
}
