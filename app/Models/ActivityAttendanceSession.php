<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ActivityAttendanceSession extends Model
{
    protected $fillable = ['activity_id', 'date', 'notes'];

    protected $casts = [
        'date' => 'date',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(ActivityAttendance::class);
    }
}
