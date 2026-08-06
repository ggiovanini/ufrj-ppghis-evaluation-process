<?php

namespace App\Models;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'candidate_name',
        'register_id',
        'title',
        'description',
        'modality',
        'indication',
        'original_content',
        'content',
        'homologation_status',
        'homologation_reason',
        'stage',
        'review_score',
        'written_exam_score',
        'committee_score',
    ];

    protected $casts = [
        'modality' => ProjectModality::class,
        'original_content' => 'array',
        'content' => 'array',
        'homologation_status' => ProjectHomologationStatus::class,
        'stage' => ProjectStage::class,
        'review_score' => 'int',
        'written_exam_score' => 'int',
        'committee_score' => 'int',
    ];

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
