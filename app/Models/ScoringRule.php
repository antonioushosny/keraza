<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScoringRule extends Model
{
    protected $fillable = [
        'season_id', 
        'class_id', 
        'weight_attendance', 
        'weight_exams', 
        'weight_memorization', 
        'weight_activities', 
        'weight_behavior'
    ];

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(KerazaClass::class, 'class_id');
    }
}
