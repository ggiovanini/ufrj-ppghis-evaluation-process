<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\ReviewAssignment;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:excess-evaluators {--selection= : ID opcional do processo seletivo}')]
#[Description('Lista projetos que possuem mais de 3 avaliadores atribuídos')]
class ProjectFindExcessEvaluatorsCommand extends Command
{
    public function handle(): int
    {
        $selectionId = $this->option('selection');

        $query = Project::query()
            ->has('reviewAssignments', '>', 3)
            ->with(['reviewAssignments.user', 'selectionProcess'])
            ->withCount('reviewAssignments');

        if ($selectionId) {
            $query->where('selection_process_id', $selectionId);
        }

        $projects = $query->get();

        if ($projects->isEmpty()) {
            $this->info('Nenhum projeto com excesso de avaliadores (>3) foi encontrado.');

            return self::SUCCESS;
        }

        $rows = $projects->map(function (Project $project) {
            $reviewersList = $project->reviewAssignments->map(function (ReviewAssignment $assignment) {
                $indicated = $assignment->chosen_by_candidate ? ' (Indicado)' : '';
                $name = $assignment->user?->name ?? 'N/A';
                $userId = $assignment->user_id;

                return "[ID: {$userId}] {$name}{$indicated}";
            })->implode("\n");

            return [
                $project->id,
                $project->candidate_name ?? $project->title,
                $project->selectionProcess?->name ?? "ID {$project->selection_process_id}",
                $project->review_assignments_count,
                $reviewersList,
            ];
        });

        $this->table(
            ['ID Projeto', 'Candidato / Título', 'Processo Seletivo', 'Total Avaliadores', 'Avaliadores (ID - Nome)'],
            $rows
        );

        $this->info("Total de projetos encontrados com excesso: {$projects->count()}");

        return self::SUCCESS;
    }
}
