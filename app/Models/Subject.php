<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Subject extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'subjects';

    protected $fillable = [
        'subject_name',
        'abbrv',
    ];

    public function subjectGradeLevels()
    {
        return $this->hasMany(SubjectGradeLevel::class);
    }
}
