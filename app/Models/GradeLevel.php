<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class GradeLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'grade_levels';

    protected $fillable = [
        'grade',
        'classification',
        'sort_order'
    ];

    public function subjectGradeLevels()
    {
        return $this->hasMany(SubjectGradeLevel::class);
    }
}
