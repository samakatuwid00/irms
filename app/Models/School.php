<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class School extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'school_name',
        'shortname',
        'school_type',
        'address',
        'contact_number',
        'email',
        'date_establish',
        'school_id',
        'legislative_district',
        'district_id',
        'created_at',
        'updated_at',
        'logo'
    ];
    protected $casts = [
        'id' => 'string',
    ];
    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class, 'district_id');
    }

    public function schoolLibraries(): HasMany
    {
        return $this->hasMany(SchoolLibrary::class, 'school_id');
    }

    public function gradeOfferings(): HasMany
    {
        return $this->hasMany(GradeOffering::class, 'school_id', 'id');
    }

    public function populations(): HasMany
    {
        return $this->hasMany(Population::class, 'school_id', 'id');
    }

    //Registration Block
    public static function getSchools(): JsonResponse
    {
        $schools = self::select('id', 'school_name', 'shortname', 'district_id', 'school_type')
            ->orderBy('school_name')
            ->get();

        return response()->json($schools);
    }
}
