<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class SubjectGradeLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subject_grade_levels';

    protected $fillable = [
        'subject_id',
        'grade_level_id',
        'key_stage',
        'curriculum_id',
    ];
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function gradeLevel()
    {
        return $this->belongsTo(GradeLevel::class);
    }

    public function curriculum()
    {
        return $this->belongsTo(Curriculum::class);
    }
}
