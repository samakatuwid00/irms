<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonPrintType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'nonprint_types';
    protected $fillable = ['id', 'type_name', 'shortname'];
    protected $casts = [
        'id' => 'string',
    ];
    public $timestamps = false;
}
