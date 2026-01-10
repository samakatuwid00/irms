<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Author extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['id', 'author_name'];

    public $timestamps = false;

    protected $casts = [
        'id' => 'string',
    ];

    public function printTitles(): BelongsToMany
    {
        return $this->belongsToMany(
            PrintTitle::class,
            'author_print_title',
            'author_id',
            'print_title_id'
        );
    }

    public function nonprintTitles(): BelongsToMany
    {
        return $this->belongsToMany(
            NonprintTitle::class,
            'author_nonprint_title',
            'author_id',
            'nonprint_title_id'
        );
    }
}
