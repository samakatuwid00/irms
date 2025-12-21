<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegionLibrary extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'region_id', 'librarian'];

    public $timestamps = false;

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class, 'region_id');
    }
}
