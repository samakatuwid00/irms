<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NonprintMasterlist extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'nonprint_acquisition_id', 'status'];

    public $timestamps = false;

    public function nonprintAcquisition(): BelongsTo
    {
        return $this->belongsTo(NonprintAcquisition::class, 'nonprint_acquisition_id');
    }
}
