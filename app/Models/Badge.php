<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Badge extends Model
{
    protected $fillable = ['title', 'icon', 'description'];

    public function studentBadges(): HasMany
    {
        return $this->hasMany(StudentBadge::class);
    }
}
