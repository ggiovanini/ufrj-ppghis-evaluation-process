<?php

namespace App\Http\Resources;

use App\Domain\Projects\Types\ProjectScore;
use App\Domain\Review\Types\ReviewStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'selection_process_id' => $this->selection_process_id,
            'candidate_name' => $this->candidate_name,
            'register_id' => $this->register_id,
            'submitted_at' => $this->submitted_at,
            'potential_duplicate' => $this->potential_duplicate ?? false,
            'duplicate_group' => $this->duplicate_group ?? null,
            'duplicate_group_size' => $this->duplicate_group_size ?? null,
            'duplicate_match_reasons' => $this->duplicate_match_reasons ?? [],
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
            'document_versions' => $this->whenLoaded('documentVersions', fn () => $this->documentVersions->map(fn ($version): array => [
                'id' => $version->id,
                'label' => $version->label,
                'name' => $version->name,
                'filename' => $version->filename,
                'mime_type' => $version->mime_type,
                'size' => $version->size,
                'version' => $version->version,
                'action' => $version->action,
                'created_at' => $version->created_at,
                'uploaded_by' => $version->user?->name,
                'url' => Storage::disk('public')->url($version->path),
            ])),
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
