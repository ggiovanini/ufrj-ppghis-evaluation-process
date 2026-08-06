<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectScore;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;

class ProjectService
{
    protected ?Project $project = null;

    public function __construct(?Project $project = null)
    {
        $this->project = $project;
    }

    public function deleteReviewAssignment(Project|ReviewAssignment|null $item = null): void
    {
        if ($item instanceof Project) {
            $this->project = $item;
        }
        if (! $this->project) {
            return;
        }

        if ($item instanceof ReviewAssignment) {
            $item->delete();
        } else {
            $this->project->reviewAssignments()->delete();
        }
        $this->project->update([
            'stage' => ProjectStage::IMPORTED,
        ]);
    }

    public function deleteAllReviewAssignment(SelectionProcess $selectionProcess): void
    {
        $selectionProcess->projects()
            ->each(fn (Project $project) => $this->deleteReviewAssignment($project));

        $selectionProcess->update([
            'phase' => SelectionProcessPhases::IMPORT,
        ]);
    }

    public function calculeReviewStepScore(): void
    {
        $reviews = $this->project->reviewAssignments->map(fn ($a) => $a->review?->score?->value)->filter();
        $this->project->update([
            'review_score' => (new ProjectScore((string) $reviews->average()))->value(),
        ]);
        $this->project->refresh();
        if ($this->project->review_score < $this->minScoreRule()) {
            $this->project->update([
                'stage' => ProjectStage::REJECTED,
            ]);
        }
    }

    public function startCommitteeReview(?Project $project = null): void
    {
        if ($project) {
            $this->project = $project;
        }

        if (! $this->project) {
            return;
        }

        if ($this->project->modality === ProjectModality::MASTER) {
            if ($this->project->writen_exam_score < $this->minScoreRule()) {
                return;
            }
        }

        if ($this->project->review_score < $this->minScoreRule()) {
            return;
        }

        $this->project->update([
            'stage' => ProjectStage::COMMITTEE,
        ]);
    }

    public function startWrittenExam(?Project $project = null): void
    {
        if ($project) {
            $this->project = $project;
        }

        if (! $this->project) {
            return;
        }

        if ($this->project->modality === ProjectModality::DOCTORATE) {
            return;
        }

        if ($this->project->review_score < $this->minScoreRule()) {
            return;
        }

        $this->project->update([
            'stage' => ProjectStage::WRITTEN_EXAM,
        ]);
    }

    protected function minScoreRule(): int
    {
        $isCoatScore = $this->project->original_content['deseja_concorrer_sob_o_sistema_de_acoes_afirmativas'] ?? 'Não';

        return (strtolower($isCoatScore[0]) === 's') ? 600 : 700;
    }
}
