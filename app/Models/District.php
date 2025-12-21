<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'district_name', 'address', 'contact_number', 'email', 'date_establish',
        'legislative_district', 'division_id', 'created_at', 'updated_at'
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'district_id');
    }
}
