<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class History extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'masterlist_id', 'status', 'created_at'];

    public $timestamps = false;
}
