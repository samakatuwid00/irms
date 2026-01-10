<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintTitle extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $casts = [
        'id' => 'string',
    ];
    protected $fillable = ['id', 'title'];

    public $timestamps = false;

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(
            Author::class,
            'author_print_title',
            'print_title_id',
            'author_id'
        );
    }

    public function printResources(): HasMany
    {
        return $this->hasMany(PrintResource::class, 'print_title_id');
    }
}
