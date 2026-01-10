<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class Region extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'region_name', 'shortname', 'address', 'contact_number', 'email', 'date_establish',
        'created_at', 'updated_at', 'logo'
    ];
    protected $casts = [
        'id' => 'string',
    ];
    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class, 'region_id');
    }

    public function regionLibraries(): HasMany
    {
        return $this->hasMany(RegionLibrary::class, 'region_id');
    }

    //Registration Block
    public static function getRegions(): JsonResponse
    {
        $regions = self::select('id', 'region_name', 'shortname')
            ->orderBy('region_name')
            ->get();

        return response()->json($regions);
    }
}
