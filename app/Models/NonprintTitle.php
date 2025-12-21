<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonprintTitle extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'title', 'author_id'];

    public $timestamps = false;

    public function author(): BelongsTo
    {
        return $this->belongsTo(Author::class, 'author_id');
    }

    public function nonprintResources(): HasMany
    {
        return $this->hasMany(NonprintResource::class, 'nonprint_title_id');
    }
}
