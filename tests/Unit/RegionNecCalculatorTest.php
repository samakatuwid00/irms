<?php

use App\Services\RegionNecCalculator;

test('all combines division-inputted and automated school NEC', function () {
    $calculator = new RegionNecCalculator();

    expect($calculator->forFilter('', 400, 600))->toBe(1000);
});

test('division library hub uses only division-inputted NEC', function () {
    $calculator = new RegionNecCalculator();

    expect($calculator->forFilter('division-hub', 400, 600))->toBe(400);
});

test('school LRs uses only automated school NEC', function () {
    $calculator = new RegionNecCalculator();

    expect($calculator->forFilter('school-hub', 400, 600))->toBe(600);
});
