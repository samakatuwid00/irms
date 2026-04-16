<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Population extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'school_id',
        'sy_id',

        'k_m','k_f','k_total',
        'g1_m','g1_f','g1_total',
        'g2_m','g2_f','g2_total',
        'g3_m','g3_f','g3_total',
        'g4_m','g4_f','g4_total',
        'g5_m','g5_f','g5_total',
        'g6_m','g6_f','g6_total',
        'g7_m','g7_f','g7_total',
        'g8_m','g8_f','g8_total',
        'g9_m','g9_f','g9_total',
        'g10_m','g10_f','g10_total',
        'g11_m','g11_f','g11_total',
        'g12_m','g12_f','g12_total',
        'ng_m','ng_f','ng_total',


        'encoded_by'
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    public function schoolYear(): BelongsTo
    {
        return $this->belongsTo(SchoolYear::class, 'sy_id');
    }
}
