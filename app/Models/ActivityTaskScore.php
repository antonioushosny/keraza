<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityTaskScore extends Model
{
    protected $fillable = ['activity_task_id', 'activity_enrollment_id', 'score', 'notes'];

    public function task(): BelongsTo
    {
        return $this->belongsTo(ActivityTask::class, 'activity_task_id');
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(ActivityEnrollment::class, 'activity_enrollment_id');
    }
}
