<?php

use App\Services\LibraryScopeService;
use App\Services\LrAggregationService;
use App\Services\LrAvailabilityService;
use App\Services\LrRatioService;
use App\Services\LrSubjectGradeHeatmapService;
use App\Services\LrSufficiencyService;
use App\Services\SchoolDashboardCurriculumScopeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    foreach (['grade_offerings', 'subject_grade_levels', 'subjects', 'grade_levels'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('grade_levels', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('grade');
        $table->unsignedInteger('sort_order');
    });

    Schema::create('subjects', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('subject_name');
        $table->string('abbrv')->nullable();
    });

    Schema::create('subject_grade_levels', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('subject_id');
        $table->string('grade_level_id');
    });

    Schema::create('grade_offerings', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('school_id');
        $table->string('K')->default('no');
        $table->string('g1')->default('no');
        $table->string('g2')->default('no');
        $table->string('g3')->default('no');
        $table->string('g4')->default('no');
        $table->string('g5')->default('no');
        $table->string('g6')->default('no');
        $table->string('g7')->default('no');
        $table->string('g8')->default('no');
        $table->string('g9')->default('no');
        $table->string('g10')->default('no');
        $table->string('g11')->default('no');
        $table->string('g12')->default('no');
        $table->string('ng')->default('no');
    });

    DB::table('grade_levels')->insert([
        ['id' => 'kindergarten', 'grade' => 'Kindergarten', 'sort_order' => 0],
        ['id' => 'grade-1', 'grade' => 'Grade 1', 'sort_order' => 1],
        ['id' => 'grade-7', 'grade' => 'Grade 7', 'sort_order' => 7],
    ]);

    DB::table('subjects')->insert([
        ['id' => 'kindergarten-domains', 'subject_name' => 'Kindergarten Domains', 'abbrv' => 'Kinder'],
        ['id' => 'mathematics', 'subject_name' => 'Mathematics', 'abbrv' => 'Math'],
        ['id' => 'science', 'subject_name' => 'Science', 'abbrv' => 'Sci'],
    ]);

    DB::table('subject_grade_levels')->insert([
        ['id' => 'sgl-kinder', 'subject_id' => 'kindergarten-domains', 'grade_level_id' => 'kindergarten'],
        ['id' => 'sgl-math-1', 'subject_id' => 'mathematics', 'grade_level_id' => 'grade-1'],
        ['id' => 'sgl-science-7', 'subject_id' => 'science', 'grade_level_id' => 'grade-7'],
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-1',
        'school_id' => 'school-1',
        'K' => 'yes',
        'g7' => 'yes',
    ]);
});

afterEach(function () {
    Mockery::close();

    foreach (['grade_offerings', 'subject_grade_levels', 'subjects', 'grade_levels'] as $table) {
        Schema::dropIfExists($table);
    }
});

test('school dashboard charts use only offered grades and their valid subject mappings', function () {
    $libraryScope = Mockery::mock(LibraryScopeService::class);
    $libraryScope->shouldReceive('getAllowedLibraryIds')->times(4)->andReturn(collect());

    $aggregation = Mockery::mock(LrAggregationService::class);
    $aggregation->shouldReceive('aggregateBySubjectGrade')->once()->andReturn(collect());

    $curriculumScope = new SchoolDashboardCurriculumScopeService;

    $availability = (new LrAvailabilityService($libraryScope, $aggregation, $curriculumScope))
        ->getChartData(null, 1, 'school-1');

    $ratio = (new LrRatioService($libraryScope, $aggregation, $curriculumScope))
        ->getChartDataCached(null, 1, 'school-1');

    $sufficiency = (new LrSufficiencyService($libraryScope, $aggregation, $curriculumScope))
        ->getSufficiencyData(null, 1, 'school-1');

    $heatmap = (new LrSubjectGradeHeatmapService($libraryScope, $aggregation, $curriculumScope))
        ->getHeatmapData(null, 1, 'school-1');

    expect($availability['grade_level'])->toBe(['Kindergarten', 'Grade 7'])
        ->and(array_column($availability['series'], 'name'))->toBe(['Kinder', 'Sci', 'Population'])
        ->and($availability['series'][0]['data'])->toBe([0, null])
        ->and($availability['series'][1]['data'])->toBe([null, 0])
        ->and($ratio['grades'])->toBe(['Kindergarten', 'Grade 7'])
        ->and(array_column($sufficiency['table_data'], 'grade'))->toBe(['Kindergarten', 'Grade 7'])
        ->and(array_column($sufficiency['table_data'], 'subject'))->toBe(['Kinder', 'Sci'])
        ->and($heatmap['x_axis'])->toBe(['Kinder', 'Sci'])
        ->and($heatmap['y_axis'])->toBe(['Kindergarten', 'Grade 7'])
        ->and($heatmap['series_data'])->toBe([[0, 0, 0], [1, 1, 0]])
        ->and($heatmap['school_curriculum_scoped'])->toBeTrue();

    $aggregateScope = $curriculumScope->resolve(3, 'division-1');

    expect($aggregateScope['grade_levels']->pluck('grade')->all())
        ->toBe(['Kindergarten', 'Grade 1', 'Grade 7'])
        ->and($aggregateScope['subjects']->pluck('abbrv')->all())
        ->toBe(['Kinder', 'Math', 'Sci'])
        ->and($aggregateScope['is_school_scoped'])->toBeFalse();
});

test('school curriculum scope is empty when grade offerings have not been configured', function () {
    DB::table('grade_offerings')->where('school_id', 'school-1')->delete();

    $scope = (new SchoolDashboardCurriculumScopeService)->resolve(1, 'school-1');

    expect($scope['grade_levels'])->toBeEmpty()
        ->and($scope['subjects'])->toBeEmpty()
        ->and($scope['message'])->toBe('No grade offerings are configured for this school.');
});
