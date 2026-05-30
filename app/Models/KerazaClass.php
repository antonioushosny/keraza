<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KerazaClass extends Model
{
    protected $table = 'classes';
    protected $fillable = ['name', 'level'];

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentSeasonEnrollment::class, 'class_id');
    }

    public function servants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_servant', 'class_id', 'user_id');
    }
}
