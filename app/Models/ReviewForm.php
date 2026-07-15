<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReviewForm extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'schema',
    ];

    protected $casts = [
        'schema' => 'json',
        'active' => 'boolean',
    ];
}
