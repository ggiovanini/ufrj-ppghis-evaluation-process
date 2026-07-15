<?php

namespace App\Models;

use App\Domain\Committee\Types\FinalStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinalResult extends Model
{
    protected $fillable = [
        'project_id',
        'review_average',
        'written_exam_score',
        'committee_score',
        'final_score',
        'status',
        'published_at',
    ];

    protected $casts = [
        'review_average' => 'float',
        'written_exam_score' => 'integer',
        'committee_score' => 'integer',
        'final_score' => 'integer',
        'published_at' => 'datetime',
        'status' => FinalStatus::class,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
