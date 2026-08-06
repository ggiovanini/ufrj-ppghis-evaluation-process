<?php

namespace App\Models;

use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_assignment_id',
        'review_form_id',
        'status',
        'score',
        'answers',
        'comments',
        'questions',
        'submitted_at',
        'pdf_path',
    ];

    protected $casts = [
        'status' => ReviewStatus::class,
        'score' => ReviewScore::class,
        'answers' => 'json',
        'submitted_at' => 'datetime',
    ];

    public function reviewAssignment(): BelongsTo
    {
        return $this->belongsTo(ReviewAssignment::class);
    }

    public function reviewForm(): BelongsTo
    {
        return $this->belongsTo(ReviewForm::class);
    }
}
