<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScore extends Model
{
    protected $fillable = ['student_season_enrollment_id', 'exam_id', 'score', 'notes'];

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(StudentSeasonEnrollment::class, 'student_season_enrollment_id');
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
