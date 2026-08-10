<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WrittenExam extends Model
{
    protected $fillable = [
        'project_id',
        'score',
        'passed',
        'user_id',
        'recorded_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'passed' => 'boolean',
        'recorded_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
