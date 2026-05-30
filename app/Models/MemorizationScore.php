<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemorizationScore extends Model
{
    protected $fillable = ['student_season_enrollment_id', 'memorization_item_id', 'score', 'accuracy', 'notes'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function memorizationItem(): BelongsTo
    {
        return $this->belongsTo(MemorizationItem::class);
    }
}
