<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Season extends Model
{
    protected $fillable = ['name', 'is_active', 'start_date', 'end_date'];

    public static function active()
    {
        return static::where('is_active', true)->first() ?? static::latest()->first();
    }

    protected static function booted()
    {
        static::saving(function ($season) {
            if ($season->is_active) {
                // Deactivate all other seasons
                static::where('id', '!=', $season->id)->update(['is_active' => false]);
            } else {
                // If deactivating this season, but no other season is active, keep this one active
                $otherActiveExists = static::where('is_active', true)->where('id', '!=', $season->id)->exists();
                if (!$otherActiveExists) {
                    $season->is_active = true;
                }
            }
        });
    }

    public function classes(): HasMany
    {
        return $this->hasMany(KerazaClass::class, 'season_id');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentSeasonEnrollment::class);
    }
}
