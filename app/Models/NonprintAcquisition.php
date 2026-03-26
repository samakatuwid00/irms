<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonprintAcquisition extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id',
        'nonprint_id',
        'source',
        'date_acquired',
        'cost',
        'iar',
        'total_qty',
        'usable',
        'partially_damaged',
        'damaged',
        'lost',
        'condemnable',
        'remarks',
        'date_encoded',
        'encoded_by',
        'curriculum_id',
        'created_at',
        'updated_at',
        'library_id',
        'library_name',
        'package_id'

    ];

    protected $table = 'nonprint_acquisitions';

    public function nonprintResource(): BelongsTo
    {
        return $this->belongsTo(NonprintResource::class, 'nonprint_id');
    }

    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function nonprintMasterlists(): HasMany
    {
        return $this->hasMany(NonprintMasterlist::class, 'nonprint_acquisition_id');
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
