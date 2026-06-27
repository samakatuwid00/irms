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
