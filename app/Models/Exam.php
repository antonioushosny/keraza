<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['category_id', 'class_id', 'season_id', 'title', 'total_score', 'date'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'category_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(KerazaClass::class, 'class_id');
    }

    public function season(): BelongsTo
    {
        return $this->belongsTo(Season::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ExamScore::class);
    }
}
