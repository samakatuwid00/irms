<?php

use App\Services\LibraryScopeService;
use App\Services\LrAggregationService;
use App\Services\LrAvailabilityService;
use App\Services\SchoolDashboardCurriculumScopeService;

afterEach(function () {
    Mockery::close();
});

test('availability series uses null for invalid subject-grade pairs and zero for valid empty pairs', function () {
    $service = new LrAvailabilityService(
        Mockery::mock(LibraryScopeService::class),
        Mockery::mock(LrAggregationService::class),
        Mockery::mock(SchoolDashboardCurriculumScopeService::class),
    );

    $subjects = collect([
        (object) ['id' => 'kindergarten-domains', 'subject_name' => 'Kindergarten Domains', 'abbrv' => 'Kinder'],
        (object) ['id' => 'mathematics', 'subject_name' => 'Mathematics', 'abbrv' => 'Math'],
    ]);

    $gradeLevels = collect([
        (object) ['id' => 'kindergarten', 'grade' => 'Kindergarten'],
        (object) ['id' => 'grade-1', 'grade' => 'Grade 1'],
    ]);

    $availableSubjectGrades = collect([
        'kindergarten-domains|kindergarten' => true,
        'mathematics|grade-1' => true,
    ]);

    $method = new ReflectionMethod(LrAvailabilityService::class, 'buildSeriesFromData');
    $method->setAccessible(true);

    $series = $method->invoke(
        $service,
        $subjects,
        $gradeLevels,
        collect(),
        $availableSubjectGrades,
        'total_qty'
    );

    expect($series[0]['data'])->toBe([0, null])
        ->and($series[1]['data'])->toBe([null, 0]);
});
