<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Population extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = [
        'id', 'male', 'female', 'total', 'sy_id', 'grade_id', 'encoded_by',
        'created_at', 'updated_at'
    ];

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'sy_id');
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }
}
