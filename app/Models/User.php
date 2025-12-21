<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'firstname', 'middlename', 'lastname', 'extension_name', 'gender', 'birthday',
        'username', 'password', 'email', 'contact_number', 'photo', 'usertype_id',
        'station_id', 'status', 'approved_by', 'created_at', 'updated_at'
    ];

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
}
