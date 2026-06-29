<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('key stage migration preserves usable UUIDs and installs the image subject matrix', function () {
    Schema::disableForeignKeyConstraints();

    foreach (['print_resource_sgl', 'print_resources', 'nonprint_resources', 'subject_grade_levels', 'grade_levels', 'subjects', 'curriculums', 'key_stages'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();

    Schema::create('curriculums', function (Blueprint $table) {
        $table->uuid('id')->primary();
    });

    Schema::create('subjects', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('subject_name');
        $table->string('abbrv', 50)->nullable();
        $table->timestamps();
    });

    Schema::create('grade_levels', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('grade');
        $table->string('classification')->nullable();
        $table->timestamps();
        $table->integer('sort_order')->nullable();
        $table->uuid('key_stage_id')->nullable();
    });

    Schema::create('subject_grade_levels', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('subject_id');
        $table->uuid('grade_level_id');
        $table->string('key_stage');
        $table->uuid('curriculum_id')->nullable();
        $table->timestamps();
        $table->unique(
            ['subject_id', 'grade_level_id', 'curriculum_id', 'key_stage'],
            'subject_grade_unique'
        );
    });

    Schema::create('print_resources', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('subject_grade_level_ids');
    });

    Schema::create('nonprint_resources', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('subject_grade_level_ids')->nullable();
    });

    Schema::create('print_resource_sgl', function (Blueprint $table) {
        $table->uuid('print_id');
        $table->uuid('sgl_id');
    });

    $legacySubjects = [
        'Araling Panlipunan' => 'AP',
        'Edukasyon sa Pagpapakatao' => 'ESP',
        'English' => 'Eng',
        'EPP/TLE/TVE' => 'EPP/TLE/TVE',
        'Filipino' => 'Fil',
        'MAPEH' => 'MAPEH',
        'Mathematics' => 'Math',
        'MTB-MLE' => 'MTB-MLE',
        'Science' => 'Sci',
    ];

    $subjectIds = [];

    foreach ($legacySubjects as $name => $abbreviation) {
        $subjectIds[$name] = (string) Str::uuid();
        DB::table('subjects')->insert([
            'id' => $subjectIds[$name],
            'subject_name' => $name,
            'abbrv' => $abbreviation,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $grades = ['Kindergarten'];

    for ($grade = 1; $grade <= 12; $grade++) {
        $grades[] = "Grade {$grade}";
    }

    $gradeIds = [];
    $legacyMappingIds = [];

    foreach ($grades as $sortOrder => $grade) {
        $legacyStage = match (true) {
            $sortOrder <= 3 => 'S1',
            $sortOrder <= 6 => 'ES',
            $sortOrder <= 10 => 'JHS',
            default => 'SHS',
        };

        $gradeIds[$grade] = (string) Str::uuid();

        DB::table('grade_levels')->insert([
            'id' => $gradeIds[$grade],
            'grade' => $grade,
            'classification' => $legacyStage,
            'sort_order' => $sortOrder,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($legacySubjects as $subject => $abbreviation) {
            if ($sortOrder >= 4 && $subject === 'MTB-MLE') {
                continue;
            }

            $mappingId = (string) Str::uuid();
            $legacyMappingIds[$grade][$subject] = $mappingId;

            DB::table('subject_grade_levels')->insert([
                'id' => $mappingId,
                'subject_id' => $subjectIds[$subject],
                'grade_level_id' => $gradeIds[$grade],
                'key_stage' => $legacyStage,
                'curriculum_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    $printResourceId = (string) Str::uuid();
    $obsoleteKindergartenApId = $legacyMappingIds['Kindergarten']['Araling Panlipunan'];
    $preservedGradeOneMathId = $legacyMappingIds['Grade 1']['Mathematics'];

    DB::table('print_resources')->insert([
        'id' => $printResourceId,
        'subject_grade_level_ids' => "{$obsoleteKindergartenApId},{$preservedGradeOneMathId}",
    ]);

    DB::table('print_resource_sgl')->insert([
        ['print_id' => $printResourceId, 'sgl_id' => $obsoleteKindergartenApId],
        ['print_id' => $printResourceId, 'sgl_id' => $preservedGradeOneMathId],
    ]);

    $migration = require database_path('migrations/2026_06_29_000000_create_key_stages_and_align_subject_mappings.php');
    $migration->up();

    expect(DB::table('key_stages')->count())->toBe(4)
        ->and(DB::table('grade_levels')->whereNull('key_stage_id')->count())->toBe(0)
        ->and(DB::table('subjects')->count())->toBe(20)
        ->and(DB::table('subject_grade_levels')->count())->toBe(85)
        ->and(Schema::hasColumn('subject_grade_levels', 'key_stage'))->toBeFalse()
        ->and(Schema::hasColumn('subject_grade_levels', 'resource_ratio'))->toBeTrue();

    $subjectNamesByGrade = fn (string $grade) => DB::table('subject_grade_levels as sgl')
        ->join('subjects as s', 's.id', '=', 'sgl.subject_id')
        ->join('grade_levels as g', 'g.id', '=', 'sgl.grade_level_id')
        ->where('g.grade', $grade)
        ->orderBy('s.subject_name')
        ->pluck('s.subject_name')
        ->all();

    expect($subjectNamesByGrade('Kindergarten'))->toBe(['Kindergarten Domains'])
        ->and($subjectNamesByGrade('Grade 1'))->toBe([
            'GMRC',
            'Language',
            'Makabansa',
            'Mathematics',
            'Reading & Literacy',
        ])
        ->and($subjectNamesByGrade('Grade 7'))->toBe([
            'Araling Panlipunan',
            'English',
            'Filipino',
            'MAPEH',
            'Mathematics',
            'Science',
            'TLE',
            'Values Education',
        ])
        ->and($subjectNamesByGrade('Grade 11'))->toBe([
            'Effective Communication',
            'General Mathematics',
            'General Science',
            'Kasaysayan at Lipunang Pilipino',
            'Life & Career Skills',
            'Specialized / Track Subjects',
        ]);

    $gradeSevenValuesEducation = DB::table('subject_grade_levels as sgl')
        ->join('subjects as s', 's.id', '=', 'sgl.subject_id')
        ->where('sgl.grade_level_id', $gradeIds['Grade 7'])
        ->where('s.subject_name', 'Values Education')
        ->first(['sgl.id', 'sgl.resource_ratio']);

    $gradeElevenGeneralMath = DB::table('subject_grade_levels as sgl')
        ->join('subjects as s', 's.id', '=', 'sgl.subject_id')
        ->where('sgl.grade_level_id', $gradeIds['Grade 11'])
        ->where('s.subject_name', 'General Mathematics')
        ->first(['sgl.id', 'sgl.resource_ratio']);

    expect($gradeSevenValuesEducation->id)->toBe($legacyMappingIds['Grade 7']['Edukasyon sa Pagpapakatao'])
        ->and($gradeSevenValuesEducation->resource_ratio)->toBe('1:2')
        ->and($gradeElevenGeneralMath->id)->toBe($legacyMappingIds['Grade 11']['Mathematics'])
        ->and($gradeElevenGeneralMath->resource_ratio)->toBe('1:2')
        ->and(DB::table('print_resources')->where('id', $printResourceId)->value('subject_grade_level_ids'))
        ->toBe($preservedGradeOneMathId)
        ->and(DB::table('print_resource_sgl')->where('print_id', $printResourceId)->pluck('sgl_id')->all())
        ->toBe([$preservedGradeOneMathId]);
});
