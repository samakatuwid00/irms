<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;

class UserType extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'type_name', 'level'];
    protected $table = 'usertypes';

    public $timestamps = false;

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'usertype_id');
    }

    //Registration Block
    public static function getUsertypes(): JsonResponse
    {
        $usertypes = self::select('id', 'type_name','level')
            ->where('level', '!=', 0)
            ->orderBy('type_name')
            ->get();

        return response()->json($usertypes);
    }
}
