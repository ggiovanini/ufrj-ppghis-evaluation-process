<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => $this->status->label(),
            'score' => $this->score,
            'score_label' => $this->score->label(),
            'score_description' => $this->score->description(),
            'answers' => $this->answers ?? [],
            'comments' => $this->comments,
            'questions' => $this->questions,
            'submitted_at' => $this->submitted_at,
            'pdf_url' => $this->pdf_path
                ? route('selection.reviews.pdf', [
                    'selection' => $this->reviewAssignment?->project?->selection_process_id,
                    'review' => $this->id,
                ])
                : null,
            'form' => [
                'id' => $this->reviewForm?->id,
                'schema' => $this->reviewForm?->schema,
            ],
        ];
    }
}
