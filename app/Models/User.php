<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use Notifiable;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'firstname',
        'middlename',
        'lastname',
        'extension_name',
        'gender',
        'birthday',
        'username',
        'password',
        'email',
        'contact_number',
        'photo',
        'usertype_id',
        'station_id',
        'status',
        'approved_by',
        'created_at',
        'updated_at'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'birthday' => 'date',
        'id' => 'string'
    ];

    protected $appends = [
        'usertype_name',
        'usertype_level',
        'station',
        'station_name'
    ];


    public function getUsertypeNameAttribute(): ?string
    {
        return $this->userType?->type_name;
    }

    public function getUsertypeLevelAttribute(): ?int
    {
        return $this->userType?->level;
    }
    /* ================= RELATIONSHIPS ================= */
    public function userType(): BelongsTo
    {
        return $this->belongsTo(UserType::class, 'usertype_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(User::class, 'approved_by');
    }

    public function populations(): HasMany
    {
        return $this->hasMany(Population::class, 'encoded_by');
    }

    public function printAcquisitions(): HasMany
    {
        return $this->hasMany(PrintAcquisition::class, 'encoded_by');
    }

    public function nonprintAcquisitions(): HasMany
    {
        return $this->hasMany(NonprintAcquisition::class, 'encoded_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(Log::class, 'user_id');
    }

    public function regionStation(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'station_id');
    }

    public function divisionStation(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'station_id');
    }

    public function districtStation(): BelongsTo
    {
        return $this->belongsTo(District::class, 'station_id');
    }

    public function schoolStation(): BelongsTo
    {
        return $this->belongsTo(School::class, 'station_id');
    }
    // Accessor for the station model (based on level)
    public function getStationAttribute()
    {
        $level = $this->usertype_level;
        return match ($level) {
            4 => $this->regionStation,
            3 => $this->divisionStation,
            2 => $this->districtStation,
            1 => $this->schoolStation,
            default => null,
        };
    }

    // Accessor for the station name
    public function getStationNameAttribute(): ?string
    {
        $station = $this->station;
        if (!$station) {
            return null;
        }

        $level = $this->usertype_level;
        return match ($level) {
            4 => $station->region_name,
            3 => $station->division_name,
            2 => $station->district_name,
            1 => $station->school_name,
            default => null,
        };
    }
}
