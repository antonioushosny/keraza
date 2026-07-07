<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityScore extends Model
{
    protected $fillable = ['activity_enrollment_id', 'activity_evaluation_id', 'raw_score', 'score', 'notes'];

    public function activityEnrollment(): BelongsTo
    {
        return $this->belongsTo(ActivityEnrollment::class);
    }

    public function activityEvaluation(): BelongsTo
    {
        return $this->belongsTo(ActivityEvaluation::class);
    }

    protected static function booted()
    {
        static::saving(function ($activityScore) {
            $maxScore = 100;
            if ($activityScore->activity_evaluation_id) {
                $maxScore = \App\Models\ActivityEvaluation::where('id', $activityScore->activity_evaluation_id)->value('max_score') ?? 100;
            }
            if ($maxScore > 0) {
                $activityScore->score = ($activityScore->raw_score / $maxScore) * 100;
            } else {
                $activityScore->score = $activityScore->raw_score;
            }
        });
    }
}
