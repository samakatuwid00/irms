<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintAcquisition extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'print_id', 'source', 'date_acquired', 'cost', 'iar',
        'total_qty', 'usable', 'partially_damaged', 'damaged', 'lost', 'condemnable',
        'remarks', 'date_encoded', 'encoded_by', 'curriculum_id', 'created_at', 'updated_at'
    ];

    public function printResource(): BelongsTo
    {
        return $this->belongsTo(PrintResource::class, 'print_id');
    }

    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class, 'curriculum_id');
    }

    public function printMasterlists(): HasMany
    {
        return $this->hasMany(PrintMasterlist::class, 'print_acquisition_id');
    }
}
