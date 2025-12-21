<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NonprintResource extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'nonprint_title_id', 'nonprint_type_id', 'brand', 'code', 'version',
        'url', 'size', 'model','description', 'subject_grade_level', 'created_at', 'updated_at'
    ];

    public function nonprintTitle(): BelongsTo
    {
        return $this->belongsTo(NonprintTitle::class, 'nonprint_title_id');
    }

    public function nonprintAcquisitions(): HasMany
    {
        return $this->hasMany(NonprintAcquisition::class, 'nonprint_id');
    }
}
