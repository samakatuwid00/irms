<?php

use App\Services\Resource\Exports\ExportPrintResourceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::dropIfExists('division_libraries');
    Schema::dropIfExists('divisions');

    Schema::create('divisions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('region_id');
    });

    Schema::create('division_libraries', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('division_id');
    });
});

afterEach(function () {
    Schema::dropIfExists('division_libraries');
    Schema::dropIfExists('divisions');
});

test('region library hub export resolves only libraries inside the selected region division', function () {
    DB::table('divisions')->insert([
        ['id' => 'division-a', 'region_id' => 'region-a'],
        ['id' => 'division-b', 'region_id' => 'region-b'],
    ]);
    DB::table('division_libraries')->insert([
        ['id' => 'library-a1', 'division_id' => 'division-a'],
        ['id' => 'library-a2', 'division_id' => 'division-a'],
        ['id' => 'library-b1', 'division_id' => 'division-b'],
    ]);

    $service = new ExportPrintResourceService();
    $method = new ReflectionMethod($service, 'getLevel4LibraryIds');
    $method->setAccessible(true);

    $allLibraries = $method->invoke(
        $service,
        Request::create('/', 'GET', [
            'tab' => 'library-hub',
            'hub_division' => 'division-a',
            'hub_library' => 'all',
        ]),
        'region-a'
    );

    $outsideLibrary = $method->invoke(
        $service,
        Request::create('/', 'GET', [
            'tab' => 'library-hub',
            'hub_division' => 'division-a',
            'hub_library' => 'library-b1',
        ]),
        'region-a'
    );

    expect($allLibraries->sort()->values()->all())->toBe(['library-a1', 'library-a2'])
        ->and($outsideLibrary)->toBeEmpty();
});

test('region library hub export uses the hub search field', function () {
    $service = new ExportPrintResourceService();
    $method = new ReflectionMethod($service, 'getSearchParam');
    $method->setAccessible(true);

    $search = $method->invoke(
        $service,
        Request::create('/', 'GET', [
            'tab' => 'library-hub',
            'hub_search' => 'science',
        ]),
        ExportPrintResourceService::LEVEL_REGION
    );

    expect($search)->toBe('science');
});
