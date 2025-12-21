<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Author extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'firstname', 'lastname'];

    public $timestamps = false;

    public function printTitles(): HasMany
    {
        return $this->hasMany(PrintTitle::class, 'author_id');
    }

    public function nonprintTitles(): HasMany
    {
        return $this->hasMany(NonprintTitle::class, 'author_id');
    }
}
