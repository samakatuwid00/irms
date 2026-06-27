<?php

use App\Services\BosyStatusService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('subjects', function (Blueprint $table) {
        $table->id();
        $table->string('name');
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
    });

    Schema::create('grade_offerings', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('school_id');

        foreach (['K', 'g1', 'g2', 'g3', 'g4', 'g5', 'g6', 'g7', 'g8', 'g9', 'g10', 'g11', 'g12', 'ng'] as $grade) {
            $table->string($grade)->default('no');
        }
    });

    DB::table('subjects')->insert([
        ['name' => 'Mathematics'],
        ['name' => 'Science'],
    ]);
    DB::table('school_years')->insert([
        'id' => 'sy-2026',
        'year_start' => 2025,
        'year_end' => 2026,
    ]);
});

function invokeBosyNecMethod(BosyStatusService $service, string $method, array $schoolIds)
{
    $reflection = new ReflectionMethod(BosyStatusService::class, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($service, $schoolIds);
}

test('NEC uses each schools distinct grade offering count', function () {
    DB::table('populations')->insert([
        ['id' => 'pop-a', 'school_id' => 'school-a', 'sy_id' => 'sy-2026', 'g1_total' => 100],
        ['id' => 'pop-b', 'school_id' => 'school-b', 'sy_id' => 'sy-2026', 'g7_total' => 50],
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-a',
        'school_id' => 'school-a',
        'g1' => 'yes',
        'g2' => 'yes',
        'g3' => 'yes',
        'g4' => 'yes',
        'g5' => 'yes',
        'g6' => 'yes',
        'ng' => 'yes',
    ]);

    DB::table('grade_offerings')->insert([
        'id' => 'offering-b',
        'school_id' => 'school-b',
        'g7' => 'yes',
        'g8' => 'yes',
        'g9' => 'yes',
        'g10' => 'yes',
    ]);

    $service = app(BosyStatusService::class);
    $necBySchool = invokeBosyNecMethod($service, 'calculateNecBySchool', ['school-a', 'school-b']);

    expect($necBySchool['school-a'])->toBe(1400)
        ->and($necBySchool['school-b'])->toBe(400)
        ->and(invokeBosyNecMethod($service, 'calculateNec', ['school-a', 'school-b']))->toBe(1800);
});
