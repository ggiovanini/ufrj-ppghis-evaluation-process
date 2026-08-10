<?php

namespace App\Http\Resources;

use App\Domain\Projects\Types\ProjectScore;
use App\Domain\Review\Types\ReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_name' => $this->candidate_name,
            'register_id' => $this->register_id,
            'title' => $this->title,
            'description' => $this->description,
            'modality' => $this->modality,
            'modality_label' => $this->modality->label(),
            'stage' => $this->stage,
            'stage_label' => $this->stage->label(),
            'rejected_on_stage' => $this->rejected_on_stage,
            'rejected_on_stage_label' => $this->rejected_on_stage?->label(),
            'indication' => $this->indication,
            'has_indication' => ! empty($this->indication),
            'original_content' => $this->original_content,
            'content' => $this->content,
            'homologation_status' => $this->homologation_status,
            'homologation_status_label' => $this->homologation_status?->label(),
            'homologation_reason' => $this->homologation_reason,
            'review_assignments' => ReviewAssignmentResource::collection($this->whenLoaded('reviewAssignments')),
            'is_evaluated' => $this->whenLoaded('reviewAssignments', function () {
                return $this->reviewAssignments->count() > 0 && $this->reviewAssignments->every(fn ($a) => $a->review?->status === ReviewStatus::SUBMITTED);
            }),
            'evaluated_percentil' => $this->whenLoaded('reviewAssignments', function () {
                return round(($this->reviewAssignments->where(fn ($a) => $a->review?->status === ReviewStatus::SUBMITTED)->count() / 3) * 100);
            }),
            'written_exam' => $this->whenLoaded('writtenExam'),
            'committee_evaluation' => $this->whenLoaded('committeeEvaluation'),
            'final_results' => $this->whenLoaded('finalResults'),
            'review_score' => $this->review_score,
            'review_score_label' => ProjectScore::make($this->review_score)->format(),
            'written_exam_score' => $this->written_exam_score,
            'written_exam_score_label' => ProjectScore::make($this->written_exam_score)->format(),
            'written_exam_score_passes' => $this->whenLoaded('writtenExam', fn () => $this->writtenExam?->passed),
            'committee_score' => $this->committee_score,
            'committee_score_label' => ProjectScore::make($this->committee_score)->format(),
            'committee_score_passes' => $this->whenLoaded('committeeEvaluation', fn () => $this->committeeEvaluation?->passed),
            'final_score' => $this->final_score,
            'final_score_label' => ProjectScore::make($this->final_score)->format(),
            'final_score_passes' => $this->whenLoaded('finalResults', fn () => $this->finalResults?->passed),
        ];
    }
}
