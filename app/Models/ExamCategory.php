<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamCategory extends Model
{
    protected $fillable = ['name'];

    public function exams(): HasMany
    {
        return $this->hasMany(Exam::class, 'category_id');
    }
}
