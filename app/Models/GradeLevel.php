<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GradeLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'grade_levels';

    protected $fillable = [
        'grade',
        'classification',
        'sort_order',
        'key_stage_id',
    ];
    protected $casts = [
        'id' => 'string',
    ];
    public function subjectGradeLevels()
    {
        return $this->hasMany(SubjectGradeLevel::class);
    }

    public function keyStage(): BelongsTo
    {
        return $this->belongsTo(KeyStage::class);
    }
}
