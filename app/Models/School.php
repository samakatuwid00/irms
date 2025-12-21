<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class School extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'school_name', 'address', 'contact_number', 'email', 'date_establish',
        'school_id', 'legislative_district', 'district_id', 'created_at', 'updated_at'
    ];

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function schoolLibraries(): HasMany
    {
        return $this->hasMany(SchoolLibrary::class, 'school_id');
    }
}
