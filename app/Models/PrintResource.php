<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrintResource extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'print_title_id', 'print_type_id', 'publisher', 'volume', 'edition',
        'copyright', 'pages', 'isbn', 'remarks', 'subject_grade_level_ids', 'created_at', 'updated_at'
    ];

    public function printTitle(): BelongsTo
    {
        return $this->belongsTo(PrintTitle::class, 'print_title_id');
    }

    public function printAcquisitions(): HasMany
    {
        return $this->hasMany(PrintAcquisition::class, 'print_id');
    }
}
