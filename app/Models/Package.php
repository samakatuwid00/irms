<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $table = 'package';
    protected $fillable = ['id', 'name', 'description'];
    protected $casts = [
        'id' => 'string',
    ];
    public $timestamps = false;
}