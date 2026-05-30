<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MemorizationItem extends Model
{
    protected $fillable = ['class_id', 'season_id', 'title', 'max_points'];

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
        return $this->hasMany(MemorizationScore::class);
    }
}
