<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'phone',
        'password',
        'type',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        // Block parents from accessing the Filament admin panel.
        if ($this->hasRole('parent') && $this->roles()->count() === 1) {
            return false;
        }
        return $this->roles()->count() > 0;
    }

    public function assignedClasses(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(KerazaClass::class, 'class_servant', 'user_id', 'class_id');
    }

    public function assignedActivities(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Activity::class, 'activity_supervisor', 'user_id', 'activity_id');
    }

    public function students(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Student::class, 'parent_id');
    }

    public static function createOrGetParent(string $phone, ?string $parentName, string $studentName): self
    {
        $parent = self::where('phone', $phone)->where('type', 'parent')->first();
        
        if (!$parent) {
            $parent = self::create([
                'name' => $parentName ?: ('ولي أمر ' . $studentName),
                'phone' => $phone,
                'type' => 'parent',
                'password' => bcrypt('123456'),
            ]);
            $parent->assignRole('parent');
        } else {
            if ($parentName && $parent->name !== $parentName) {
                $parent->update(['name' => $parentName]);
            }
            if (!$parent->hasRole('parent')) {
                $parent->assignRole('parent');
            }
        }
        
        return $parent;
    }
}
