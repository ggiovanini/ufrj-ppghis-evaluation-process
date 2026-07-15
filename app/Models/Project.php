<?php

namespace App\Models;

use App\Domain\Projects\Types\ProjectModality;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'candidate_name',
        'title',
        'modality',
        'original_content',
        'content',
        'stage',
    ];

    protected function casts(): array
    {
        return [
            'modality' => ProjectModality::class,
            'original_content' => 'array',
            'content' => 'array',
        ];
    }

    public function selectionProcess(): BelongsTo
    {
        return $this->belongsTo(SelectionProcess::class);
    }

    public function reviewAssignments(): HasMany
    {
        return $this->hasMany(ReviewAssignment::class);
    }

    public function writtenExam(): HasOne
    {
        return $this->hasOne(WrittenExam::class);
    }

    public function committeeEvaluation(): HasOne
    {
        return $this->hasOne(CommitteeEvaluation::class);
    }

    public function finalResults(): HasOne
    {
        return $this->hasOne(FinalResult::class);
    }
}
