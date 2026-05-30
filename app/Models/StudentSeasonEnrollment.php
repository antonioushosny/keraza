<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentSeasonEnrollment extends Model
{
    protected $fillable = ['student_id', 'season_id', 'class_id'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(KerazaClass::class, 'class_id');
    }

    public function attendance(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_season_enrollment_id');
    }

    public function examScores(): HasMany
    {
        return $this->hasMany(ExamScore::class, 'student_season_enrollment_id');
    }

    public function memorizationScores(): HasMany
    {
        return $this->hasMany(MemorizationScore::class, 'student_season_enrollment_id');
    }

    public function activityEnrollments(): HasMany
    {
        return $this->hasMany(ActivityEnrollment::class, 'student_season_enrollment_id');
    }

    public function behaviorLogs(): HasMany
    {
        return $this->hasMany(BehaviorLog::class, 'student_season_enrollment_id');
    }

    public function badges(): HasMany
    {
        return $this->hasMany(StudentBadge::class, 'student_season_enrollment_id');
    }
}
