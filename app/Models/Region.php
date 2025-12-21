<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Region extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'region_name', 'address', 'contact_number', 'email', 'date_establish',
        'created_at', 'updated_at'
    ];

    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'region_id');
    }

    public function regionLibraries(): HasMany
    {
        return $this->hasMany(RegionLibrary::class, 'region_id');
    }
}
