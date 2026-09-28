<?php

namespace App\Console\Commands;

use App\Domain\Review\Types\ReviewStatus;
use App\Models\ReviewAssignment;
use App\Models\ReviewForm;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:fix-missing-reviews {--selection= : ID opcional do processo seletivo} {--dry-run : Simular correções sem gravar no banco}')]
#[Description('Identifica e cria registros de Review pendentes para ReviewAssignments órfãos')]
class ProjectFixMissingReviewsCommand extends Command
{
    public function handle(): int
    {
        $selectionId = $this->option('selection');
        $dryRun = (bool) $this->option('dry-run');

        $query = ReviewAssignment::query()
            ->whereDoesntHave('review')
            ->with(['project.selectionProcess', 'user']);

        if ($selectionId) {
            $query->whereHas('project', function ($q) use ($selectionId): void {
                $q->where('selection_process_id', $selectionId);
            });
        }

        $missingAssignments = $query->get();

        if ($missingAssignments->isEmpty()) {
            $this->info('Nenhuma atribuição sem avaliação encontrada.');

            return self::SUCCESS;
        }

        $defaultReviewFormId = ReviewForm::where('active', true)->first()?->id ?? ReviewForm::first()?->id;

        $createdCount = 0;
        $rows = [];

        foreach ($missingAssignments as $assignment) {
            $project = $assignment->project;
            $selection = $project?->selectionProcess;
            $formId = $selection?->review_form_id ?? $defaultReviewFormId;

            if (! $formId) {
                $this->warn("Aviso: Nenhum formulário de avaliação encontrado para a atribuição ID {$assignment->id}. Pulando.");

                continue;
            }

            if (! $dryRun) {
                $assignment->review()->create([
                    'review_form_id' => $formId,
                    'status' => ReviewStatus::PENDENT,
                ]);
                $createdCount++;
                $action = 'Criado';
            } else {
                $createdCount++;
                $action = 'Simulado';
            }

            $rows[] = [
                $assignment->id,
                $project?->id ?? 'N/A',
                $project?->candidate_name ?? $project?->title ?? 'N/A',
                $assignment->user?->name ?? "User ID {$assignment->user_id}",
                $selection?->name ?? "ID {$project?->selection_process_id}",
                $formId,
                $action,
            ];
        }

        $this->table(
            ['ID Atribuição', 'ID Projeto', 'Candidato / Título', 'Avaliador', 'Processo Seletivo', 'ID Formulário', 'Status'],
            $rows
        );

        if ($dryRun) {
            $this->info("Dry-run concluído: {$createdCount} avaliações seriam criadas.");
        } else {
            $this->info("Sucesso: {$createdCount} avaliações pendentes foram criadas.");
        }

        return self::SUCCESS;
    }
}
