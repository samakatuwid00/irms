<?php

use App\Models\User;
use App\Models\UserType;
use App\Services\SchoolPopulationRequirementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
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
});

function populationRequirementUser(int $level = 1): User
{
    $user = new User(['station_id' => 'school-1']);
    $user->setRelation('userType', new UserType(['level' => $level]));

    return $user;
}

test('school users without population are required to update it', function () {
    $service = app(SchoolPopulationRequirementService::class);

    expect($service->isRequired(populationRequirementUser()))->toBeTrue();
});

test('the latest population school year determines whether an update is required', function () {
    DB::table('school_years')->insert([
        ['id' => 'sy-old', 'year_start' => 2024, 'year_end' => 2025],
        ['id' => 'sy-new', 'year_start' => 2025, 'year_end' => 2026],
    ]);

    DB::table('populations')->insert([
        ['id' => 'pop-old', 'school_id' => 'school-1', 'sy_id' => 'sy-old', 'g1_total' => 50],
        ['id' => 'pop-new', 'school_id' => 'school-1', 'sy_id' => 'sy-new', 'g1_total' => 0],
    ]);

    $service = app(SchoolPopulationRequirementService::class);
    $user = populationRequirementUser();

    expect($service->isRequired($user))->toBeTrue();

    DB::table('populations')->where('id', 'pop-new')->update(['g1_total' => 40]);

    expect($service->isRequired($user))->toBeFalse();
});

test('non-school users are never subject to the population gate', function () {
    $service = app(SchoolPopulationRequirementService::class);

    expect($service->isRequired(populationRequirementUser(3)))->toBeFalse();
});
