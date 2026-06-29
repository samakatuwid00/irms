<?php

use App\Models\User;
use App\Models\UserType;
use App\Services\DivisionResourceRequirementService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    Schema::create('division_libraries', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('division_id');
        $table->unsignedBigInteger('net_expected_count')->default(0);
    });
});

function divisionRequirementUser(int $level = 3): User
{
    $user = new User(['station_id' => 'division-1']);
    $user->setRelation('userType', new UserType(['level' => $level]));

    return $user;
}

test('division users without a positive manual NEC are required to update it', function () {
    $service = app(DivisionResourceRequirementService::class);
    $user = divisionRequirementUser();

    expect($service->isRequired($user))->toBeTrue();

    DB::table('division_libraries')->insert([
        'id' => 'library-1',
        'division_id' => 'division-1',
        'net_expected_count' => 0,
    ]);

    expect($service->isRequired($user))->toBeTrue();
});

test('a positive division-inputted NEC removes the restriction', function () {
    DB::table('division_libraries')->insert([
        'id' => 'library-1',
        'division_id' => 'division-1',
        'net_expected_count' => 125,
    ]);

    $service = app(DivisionResourceRequirementService::class);

    expect($service->isRequired(divisionRequirementUser()))->toBeFalse();
});

test('non-division users are not subject to the division resource gate', function () {
    $service = app(DivisionResourceRequirementService::class);

    expect($service->isRequired(divisionRequirementUser(4)))->toBeFalse();
});
