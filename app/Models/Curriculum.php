<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curriculum extends Model
{
    public $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['id', 'name', 'description', 'implementation_year'];
    protected $casts = [
        'id' => 'string',
    ];

    public $timestamps = false;

    public function printAcquisitions(): HasMany
    {
        return $this->hasMany(PrintAcquisition::class, 'curriculum_id');
    }

    public function nonprintAcquisitions(): HasMany
    {
        return $this->hasMany(NonprintAcquisition::class, 'curriculum_id');
    }

    public function subjectGradeLevels(): HasMany
    {
        return $this->hasMany(SubjectGradeLevel::class, 'curriculum_id');
    }
}
