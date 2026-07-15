<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewAssignment extends Model
{
    protected $fillable = [
        'review_form_id',
        'project_id',
        'chosen_by_candidate',
    ];

    protected $casts = [
        'chosen_by_candidate' => 'boolean',
    ];

    public function reviewForm(): BelongsTo
    {
        return $this->belongsTo(ReviewForm::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
