<?php

namespace App\Models;

use App\Domain\Review\Types\ReviewStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'review_assignment_id',
        'review_form_id',
        'status',
        'score',
        'answers',
        'comments',
        'submitted_at',
    ];

    protected $casts = [
        'status' => ReviewStatus::class,
        'answers' => 'json',
        'score' => 'integer',
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
