<?php

namespace App\Models;

use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SelectionProcess extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'year',
    ];

    protected function casts(): array
    {
        return [
            'phase' => SelectionProcessPhases::class,
            'starts_at' => 'timestamp',
            'ends_at' => 'timestamp',
            'published_at' => 'timestamp',
        ];
    }

    public function reviewForm(): BelongsTo
    {
        return $this->belongsTo(ReviewForm::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }
}
