<?php

namespace App\Console\Commands;

use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('projects:remove-evaluator {project : ID do projeto} {user : ID do avaliador} {--force : Executar sem confirmação interativa}')]
#[Description('Remove um avaliador atribuído a um projeto')]
class ProjectRemoveEvaluatorCommand extends Command
{
    public function handle(): int
    {
        $projectId = $this->argument('project');
        $userId = $this->argument('user');

        $project = Project::with(['selectionProcess'])->find($projectId);

        if (! $project) {
            $this->error("Projeto com ID {$projectId} não foi encontrado.");

            return self::FAILURE;
        }

        $assignment = $project->reviewAssignments()->with('user')->where('user_id', $userId)->first();

        if (! $assignment) {
            $this->error("Avaliador com ID {$userId} não está atribuído ao projeto {$projectId}.");

            return self::FAILURE;
        }

        $reviewerName = $assignment->user?->name ?? "ID {$userId}";
        $candidateName = $project->candidate_name ?? $project->title;
        $isIndicated = $assignment->chosen_by_candidate ? 'Sim' : 'Não';
        $currentCount = $project->reviewAssignments()->count();

        $this->info("Projeto: [ID {$project->id}] {$candidateName}");
        $this->info("Avaliador a ser removido: [ID {$userId}] {$reviewerName} (Indicação: {$isIndicated})");
        $this->info("Total atual de avaliadores: {$currentCount}");

        if (! $this->option('force')) {
            if (! $this->confirm('Deseja realmente remover este avaliador do projeto?', true)) {
                $this->warn('Operação cancelada pelo usuário.');

                return self::SUCCESS;
            }
        }

        $assignment->delete();

        $remainingCount = $project->reviewAssignments()->count();

        if ($remainingCount < 3) {
            $project->update([
                'stage' => ProjectStage::IMPORTED,
            ]);
        }

        $this->info("Avaliador [ID {$userId}] {$reviewerName} removido com sucesso do projeto [ID {$project->id}].");
        $this->info("Total restante de avaliadores no projeto: {$remainingCount}");

        return self::SUCCESS;
    }
}
