<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grade extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'grade', 'classification'];

    public $timestamps = false;

    public function populations(): HasMany
    {
        return $this->hasMany(Population::class, 'grade_id');
    }
}
