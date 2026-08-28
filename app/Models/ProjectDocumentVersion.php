<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectDocumentVersion extends Model
{
    protected $fillable = ['project_id', 'user_id', 'label', 'name', 'filename', 'path', 'mime_type', 'size', 'version', 'action'];

    protected $casts = ['size' => 'integer', 'version' => 'integer'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
