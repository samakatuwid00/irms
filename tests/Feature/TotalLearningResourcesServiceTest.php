<?php

use App\Services\TotalLearningResourcesService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('division_libraries', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('division_id');
    });

    Schema::create('districts', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('division_id');
    });

    Schema::create('schools', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('district_id');
    });

    Schema::create('school_libraries', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('school_id');
    });

    Schema::create('print_acquisitions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('library_id');
        $table->integer('total_qty')->default(0);
    });

    Schema::create('nonprint_acquisitions', function (Blueprint $table) {
        $table->string('id')->primary();
        $table->string('library_id');
        $table->integer('total_qty')->default(0);
    });
});

test('division source totals separate division hubs from schools in that division', function () {
    DB::table('division_libraries')->insert([
        ['id' => 'division-library-1', 'division_id' => 'division-1'],
        ['id' => 'division-library-2', 'division_id' => 'division-2'],
    ]);
    DB::table('districts')->insert([
        ['id' => 'district-1', 'division_id' => 'division-1'],
        ['id' => 'district-2', 'division_id' => 'division-2'],
    ]);
    DB::table('schools')->insert([
        ['id' => 'school-1', 'district_id' => 'district-1'],
        ['id' => 'school-2', 'district_id' => 'district-2'],
    ]);
    DB::table('school_libraries')->insert([
        ['id' => 'school-library-1', 'school_id' => 'school-1'],
        ['id' => 'school-library-2', 'school_id' => 'school-2'],
    ]);
    DB::table('print_acquisitions')->insert([
        ['id' => 'print-1', 'library_id' => 'division-library-1', 'total_qty' => 10],
        ['id' => 'print-2', 'library_id' => 'school-library-1', 'total_qty' => 30],
        ['id' => 'print-3', 'library_id' => 'division-library-2', 'total_qty' => 1000],
    ]);
    DB::table('nonprint_acquisitions')->insert([
        ['id' => 'nonprint-1', 'library_id' => 'division-library-1', 'total_qty' => 5],
        ['id' => 'nonprint-2', 'library_id' => 'school-library-1', 'total_qty' => 7],
        ['id' => 'nonprint-3', 'library_id' => 'school-library-2', 'total_qty' => 1000],
    ]);

    $totals = app(TotalLearningResourcesService::class)
        ->getDivisionSourceTotals('division-1');

    expect($totals)->toBe([
        'division_lr_hub' => 15,
        'school_lr' => 37,
    ]);
});
