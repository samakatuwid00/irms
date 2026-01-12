<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonprintTitle extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'title'];

    protected $table = 'nonprint_titles';

    public $timestamps = false;

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            Author::class,
            'author_nonprint_title',
            'nonprint_title_id',
            'author_id'
        );
    }

    public function nonprintResources(): HasMany
    {
        return $this->hasMany(NonprintResource::class, 'nonprint_title_id');
    }
}
