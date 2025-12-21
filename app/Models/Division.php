<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'division_name', 'address', 'contact_number', 'email', 'date_establish',
        'legislative_district', 'region_id', 'created_at', 'updated_at'
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'division_id');
    }

    public function divisionLibraries(): HasMany
    {
        return $this->hasMany(DivisionLibrary::class, 'division_id');
    }
}
