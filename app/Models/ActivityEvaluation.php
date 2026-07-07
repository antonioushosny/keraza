<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityEvaluation extends Model
{
    protected $fillable = ['activity_id', 'max_score', 'date', 'notes'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ActivityScore::class, 'activity_evaluation_id');
    }
}
