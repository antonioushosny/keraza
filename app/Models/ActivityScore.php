<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityScore extends Model
{
    protected $fillable = ['activity_enrollment_id', 'score', 'notes'];

    public function activityEnrollment(): BelongsTo
    {
        return $this->belongsTo(ActivityEnrollment::class);
    }
}
