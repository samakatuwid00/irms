<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'type_name', 'level'];

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'usertype_id');
    }
}
