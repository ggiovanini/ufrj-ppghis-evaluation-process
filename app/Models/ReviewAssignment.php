<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'chosen_by_candidate',
    ];

    protected $casts = [
        'chosen_by_candidate' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewForm(): BelongsTo
    {
        return $this->belongsTo(ReviewForm::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
