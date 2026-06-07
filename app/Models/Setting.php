<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'honor_roll_limit_enabled',
        'honor_roll_limit',
        'show_zero_scores',
        'show_attendance_percentage',
    ];

    /**
     * Get or create the general settings record.
     */
    public static function getSettings(): self
    {
        return self::firstOrCreate([], [
            'honor_roll_limit_enabled' => true,
            'honor_roll_limit' => 15,
            'show_zero_scores' => true,
            'show_attendance_percentage' => true,
        ]);
    }
}
