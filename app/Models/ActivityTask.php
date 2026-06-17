<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityTask extends Model
{
    protected $fillable = ['activity_id', 'title', 'max_score', 'date', 'notes'];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function taskScores(): HasMany
    {
        return $this->hasMany(ActivityTaskScore::class);
    }
}
