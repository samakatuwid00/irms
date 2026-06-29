<?php

use App\Services\BosyStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('subjects', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('subject_name');
    });

    Schema::create('grade_levels', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('grade');
    });

    Schema::create('subject_grade_levels', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('subject_id');
        $table->string('grade_level_id');
    });

    Schema::create('school_years', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->integer('year_start');
        $table->integer('year_end');
    });

    Schema::create('populations', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('school_id');
        $table->uuid('sy_id');

        foreach (['k', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12'] as $grade) {
            $table->integer("{$grade}_total")->default(0);
        }

        $table->integer('ng_total')->default(0);
    });

    Schema::create('grade_offerings', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('school_id');

        foreach (['K', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12', 'ng'] as $grade) {
            $table->string($grade)->default('no');
        }
    });

    DB::table('subjects')->insert([
        ['id' => 'subject-math', 'subject_name' => 'Mathematics'],
        ['id' => 'subject-language', 'subject_name' => 'Language'],
        ['id' => 'subject-filipino', 'subject_name' => 'Filipino'],
        ['id' => 'subject-science', 'subject_name' => 'Science'],
        ['id' => 'subject-ap', 'subject_name' => 'Araling Panlipunan'],
    ]);

    DB::table('grade_levels')->insert([
        ['id' => 'grade-1', 'grade' => 'Grade 1'],
        ['id' => 'grade-2', 'grade' => 'Grade 2'],
        ['id' => 'grade-7', 'grade' => 'Grade 7'],
    ]);

    DB::table('subject_grade_levels')->insert([
        ['id' => 'g1-math', 'grade_level_id' => 'grade-1', 'subject_id' => 'subject-math'],
        ['id' => 'g1-language', 'grade_level_id' => 'grade-1', 'subject_id' => 'subject-language'],
        ['id' => 'g2-math', 'grade_level_id' => 'grade-2', 'subject_id' => 'subject-math'],
        ['id' => 'g2-filipino', 'grade_level_id' => 'grade-2', 'subject_id' => 'subject-filipino'],
        ['id' => 'g7-math', 'grade_level_id' => 'grade-7', 'subject_id' => 'subject-math'],
        ['id' => 'g7-science', 'grade_level_id' => 'grade-7', 'subject_id' => 'subject-science'],
        ['id' => 'g7-ap', 'grade_level_id' => 'grade-7', 'subject_id' => 'subject-ap'],
    ]);
    DB::table('school_years')->insert([
        ['id' => 'sy-2025', 'year_start' => 2024, 'year_end' => 2025],
        ['id' => 'sy-2026', 'year_start' => 2025, 'year_end' => 2026],
    ]);
});

function invokeBosyNecMethod(BosyStatusService $service, string $method, array $schoolIds)
{
    $reflection = new ReflectionMethod(BosyStatusService::class, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($service, $schoolIds);
}

test('NEC uses latest population, formal grade offerings, and their distinct subject areas', function () {
    DB::table('populations')->insert([
        ['id' => 'pop-a-old', 'school_id' => 'school-a', 'sy_id' => 'sy-2025', 'g1_total' => 999, 'g7_total' => 0, 'ng_total' => 0],
        ['id' => 'pop-a-new', 'school_id' => 'school-a', 'sy_id' => 'sy-2026', 'g1_total' => 100, 'g7_total' => 0, 'ng_total' => 10],
        ['id' => 'pop-b-new', 'school_id' => 'school-b', 'sy_id' => 'sy-2026', 'g1_total' => 0, 'g7_total' => 50, 'ng_total' => 5],
        ['id' => 'pop-c-new', 'school_id' => 'school-c', 'sy_id' => 'sy-2026', 'g1_total' => 0, 'g7_total' => 0, 'ng_total' => 20],
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-a',
        'school_id' => 'school-a',
        'g1' => 'yes',
        'g2' => 'yes',
        'ng' => 'yes',
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-b',
        'school_id' => 'school-b',
        'g7' => 'yes',
        'ng' => 'yes',
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-c',
        'school_id' => 'school-c',
        'ng' => 'yes',
    ]);

    $service = app(BosyStatusService::class);
    $schoolIds = ['school-a', 'school-b', 'school-c'];
    $necBySchool = invokeBosyNecMethod($service, 'calculateNecBySchool', $schoolIds);
    $gradeOfferingCounts = invokeBosyNecMethod($service, 'getGradeOfferingCountsBySchool', $schoolIds);

    expect($gradeOfferingCounts->all())->toBe([
        'school-a' => 2,
        'school-b' => 1,
        'school-c' => 0,
    ])->and($necBySchool['school-a'])->toBe(660) // 110 latest population × 2 grades × 3 distinct subjects
        ->and($necBySchool['school-b'])->toBe(165) // 55 latest population × 1 grade × 3 distinct subjects
        ->and($necBySchool['school-c'])->toBe(0) // Non-Graded is not a grade-offering factor
        ->and(invokeBosyNecMethod($service, 'calculateNec', $schoolIds))->toBe(825);
});
