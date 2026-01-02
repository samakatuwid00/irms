<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class Division extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'division_name', 'shortname', 'address', 'contact_number', 'email', 'date_establish',
        'legislative_district', 'region_id', 'logo', 'created_at', 'updated_at'
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

    //Registration Block
    public static function getDivisions(): JsonResponse
    {
        $divisions = self::select('id', 'division_name', 'shortname', 'region_id')
            ->orderBy('division_name')
            ->get();

        return response()->json($divisions);
    }
}
