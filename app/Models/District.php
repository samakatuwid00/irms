<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class District extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'district_name', 'shortname', 'address', 'contact_number', 'email', 'date_establish',
        'legislative_district', 'division_id', 'logo', 'created_at', 'updated_at', 'logo'
    ];

    protected $casts = [
        'id' => 'string',
    ];

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class, 'division_id');
    }

    public function schools(): HasMany
    {
        return $this->hasMany(School::class, 'district_id');
    }

    //Registration Block
    public static function getDistricts(): JsonResponse
    {
        $districts = self::select('id', 'district_name', 'shortname', 'division_id')
            ->orderBy('district_name')
            ->get();

        return response()->json($districts);
    }
}
