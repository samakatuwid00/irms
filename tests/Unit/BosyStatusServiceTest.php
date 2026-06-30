<?php

use App\Services\BosyStatusService;
use App\Services\RegionNecCalculator;

function bosyStatusServiceMethod(string $method): ReflectionMethod
{
    $reflection = new ReflectionMethod(BosyStatusService::class, $method);
    $reflection->setAccessible(true);

    return $reflection;
}

test('BOSY progress uses NEC as its only denominator', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculatePercentage = bosyStatusServiceMethod('calculatePercentage');

    expect($calculatePercentage->invoke($service, 50, 100))->toBe(50)
        ->and($calculatePercentage->invoke($service, 50, 0))->toBe(0);
});

test('division schools with zero NEC receive no population status', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $determineStatus = bosyStatusServiceMethod('determineDivisionSchoolStatus');

    expect($determineStatus->invoke($service, 0, 0))->toBe('No Population')
        ->and($determineStatus->invoke($service, 100, 0))->toBe('Not Started');
});

test('BOSY status supports decimal School LR completion', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $determineStatus = bosyStatusServiceMethod('determineBosyStatus');

    expect($determineStatus->invoke($service, 0.0))->toBe('Not Started')
        ->and($determineStatus->invoke($service, 0.5))->toBe('Partial')
        ->and($determineStatus->invoke($service, 99.99))->toBe('In-review')
        ->and($determineStatus->invoke($service, 100.0))->toBe('Complete');
});

test('school dashboard empty responses do not expose NEC', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $emptyResponse = bosyStatusServiceMethod('emptyResponse');

    $schoolResponse = $emptyResponse->invoke($service, 'school', 'school-1');
    $divisionResponse = $emptyResponse->invoke($service, 'division', 'division-1');

    expect($schoolResponse['summary'])->not->toHaveKey('net_expected_count')
        ->and($divisionResponse['summary'])->toHaveKey('net_expected_count');
});

test('region School LRs averages the completion of every eligible school', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculateSummary = bosyStatusServiceMethod('calculateAverageCompletionSummary');

    $schoolIds = [];
    $actualBySchool = [];
    $necBySchool = [];

    foreach (range(1, 45) as $number) {
        $schoolId = "school-{$number}";
        $schoolIds[] = $schoolId;
        $actualBySchool[$schoolId] = $number <= 40 ? 100 : 80;
        $necBySchool[$schoolId] = 100;
    }

    $summary = $calculateSummary->invoke(
        $service,
        $schoolIds,
        $actualBySchool,
        $necBySchool
    );

    expect($summary['percentage'])->toBe(97.78)
        ->and($summary['eligible_items'])->toBe(45)
        ->and($summary['completed_items'])->toBe(40);
});

test('region School LRs reports partial progress even when no school is complete', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculateSummary = bosyStatusServiceMethod('calculateAverageCompletionSummary');

    $summary = $calculateSummary->invoke(
        $service,
        ['school-1', 'school-2'],
        ['school-1' => 80, 'school-2' => 80],
        ['school-1' => 100, 'school-2' => 100]
    );

    expect($summary['percentage'])->toBe(80.0)
        ->and($summary['completed_items'])->toBe(0);
});

test('region School LRs caps each school and excludes zero NEC schools', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculateSummary = bosyStatusServiceMethod('calculateAverageCompletionSummary');

    $summary = $calculateSummary->invoke(
        $service,
        ['over-target', 'partial', 'no-population'],
        ['over-target' => 120, 'partial' => 80, 'no-population' => 500],
        ['over-target' => 100, 'partial' => 100, 'no-population' => 0]
    );

    expect($summary['percentage'])->toBe(90.0)
        ->and($summary['eligible_items'])->toBe(2)
        ->and($summary['completed_items'])->toBe(1)
        ->and($summary['total_lr'])->toBe(700)
        ->and($summary['net_expected_count'])->toBe(200);
});

test('region Division Library Hub uses the single hub completion', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculateSummary = bosyStatusServiceMethod('calculateAverageCompletionSummary');

    $summary = $calculateSummary->invoke(
        $service,
        ['hub-1'],
        ['hub-1' => 60],
        ['hub-1' => 120]
    );

    expect($summary['percentage'])->toBe(50.0)
        ->and($summary['eligible_items'])->toBe(1)
        ->and($summary['completed_items'])->toBe(0)
        ->and($summary['net_expected_count'])->toBe(120);
});

test('region Division Library Hub averages multiple capped hub completions', function () {
    $service = new BosyStatusService(new RegionNecCalculator());
    $calculateSummary = bosyStatusServiceMethod('calculateAverageCompletionSummary');

    $summary = $calculateSummary->invoke(
        $service,
        ['over-target-hub', 'partial-hub'],
        ['over-target-hub' => 150, 'partial-hub' => 80],
        ['over-target-hub' => 100, 'partial-hub' => 100]
    );

    expect($summary['percentage'])->toBe(90.0)
        ->and($summary['eligible_items'])->toBe(2)
        ->and($summary['completed_items'])->toBe(1)
        ->and($summary['total_lr'])->toBe(230)
        ->and($summary['net_expected_count'])->toBe(200);
});
