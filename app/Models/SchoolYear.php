<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolYear extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'year_start', 'year_end'];

    public $timestamps = false;

    public function populations(): HasMany
    {
        return $this->hasMany(Population::class, 'sy_id');
    }
}
