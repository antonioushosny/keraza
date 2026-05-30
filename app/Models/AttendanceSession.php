<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AttendanceSession extends Model
{
    protected $fillable = ['season_id', 'class_id', 'date', 'notes'];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(KerazaClass::class, 'class_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }
}
