<?php

namespace App\Domain\Review\Services;

use App\Models\Review;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ReviewPdfService
{
    public function generate(Review $review): string
    {
        $review->load([
            'reviewAssignment.user',
            'reviewAssignment.project',
            'reviewForm',
        ]);

        $path = 'reviews/'.$review->id.'.pdf';
        $pdf = Pdf::loadView('reviews.pdf', [
            'review' => $review,
            'project' => $review->reviewAssignment->project,
            'reviewer' => $review->reviewAssignment->user,
            'fields' => $review->reviewForm?->schema['fields'] ?? [],
        ])->setPaper('a4');

        Storage::disk('local')->put($path, $pdf->output());
        $review->update(['pdf_path' => $path]);

        return $path;
    }
}
