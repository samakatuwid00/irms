<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeOffering extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'grade_offerings';

    protected $fillable = [
        'id',
        'school_id',
        'K',
        'g1',
        'g2',
        'g3',
        'g4',
        'g5',
        'g6',
        'g7',
        'g8',
        'g9',
        'g10',
        'g11',
        'g12'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id', 'id');
    }
}
