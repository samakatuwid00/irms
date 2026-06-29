<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    SubjectGradeLevel,
    PrintType
};

class LookUpController extends Controller
{
    public function getSubjectGradeLevel()
    {
        $subjectGradeLevels = SubjectGradeLevel::query()
            ->select(
                'subject_grade_levels.id as subject_grade_level_id',
                'subject_grade_levels.subject_id',
                'subject_grade_levels.grade_level_id',
                'subjects.subject_name',
                'grade_levels.grade as grade_level',
                'key_stages.name as key_stage',
                'grade_levels.sort_order'
            )
            ->join('subjects', 'subjects.id', '=', 'subject_grade_levels.subject_id')
            ->join('grade_levels', 'grade_levels.id', '=', 'subject_grade_levels.grade_level_id')
            ->join('key_stages', 'key_stages.id', '=', 'grade_levels.key_stage_id')
            ->orderBy('grade_levels.sort_order')
            ->get();

        return response()->json($subjectGradeLevels);
    }
    public function getTypes()
    {
        $printTypes = PrintType::all();
        return response()->json($printTypes);
    }
}
