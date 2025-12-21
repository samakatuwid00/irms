<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintMasterlist extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'print_acquisition_id', 'status'];

    public $timestamps = false;

    public function printAcquisition(): BelongsTo
    {
        return $this->belongsTo(PrintAcquisition::class, 'print_acquisition_id');
    }
}
