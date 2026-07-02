<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityScoringRule extends Model
{
    protected $fillable = [
        'season_id',
        'weight_attendance',
        'weight_tasks',
        'weight_evaluation',
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }
}
