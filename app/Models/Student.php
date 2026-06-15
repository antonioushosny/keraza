<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = ['code', 'parent_id', 'full_name', 'gender', 'birth_date', 'profile_image', 'notes'];

    protected static function booted()
    {
        static::addGlobalScope('alphabetical', function ($builder) {
            $builder->orderBy('full_name', 'asc');
        });

        static::creating(function ($student) {
            if (!$student->code) {
                $maxCode = static::max('code');
                $nextCode = $maxCode && is_numeric($maxCode) ? intval($maxCode) + 1 : 10001;
                $student->code = strval($nextCode);
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentSeasonEnrollment::class);
    }
}
