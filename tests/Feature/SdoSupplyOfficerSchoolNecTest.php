<?php

use App\Http\Controllers\DashboardController;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const SDO_SUPPLY_OFFICER_TYPE_ID = 'fd43d1da-64c7-4be2-9f2c-d419f599404f';

beforeEach(function () {
    Schema::create('districts', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('division_id');
    });

    Schema::create('schools', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('school_name');
        $table->uuid('district_id');
        $table->timestamps();
    });

    Schema::create('school_libraries', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('school_id');
        $table->unsignedBigInteger('estimated_resource')->default(0);
    });

    DB::table('districts')->insert([
        'id' => 'district-1',
        'division_id' => 'division-1',
    ]);

    DB::table('schools')->insert([
        'id' => 'school-1',
        'school_name' => 'Test School',
        'district_id' => 'district-1',
    ]);

    DB::table('school_libraries')->insert([
        'id' => 'library-1',
        'school_id' => 'school-1',
        'estimated_resource' => 0,
    ]);
});

function sdoSchoolNecUser(string $userTypeId, string $divisionId): User
{
    $user = new User([
        'usertype_id' => $userTypeId,
        'station_id' => $divisionId,
    ]);
    $user->id = 'user-1';

    return $user;
}

function updateSchoolNecAs(User $user, int $estimatedResource)
{
    Auth::setUser($user);

    $request = Request::create('/dashboard/bosy-schools/school-1/nec', 'PATCH', [
        'estimated_resource' => $estimatedResource,
    ]);

    return app(DashboardController::class)->updateSchoolNec(
        $request,
        School::query()->findOrFail('school-1')
    );
}

test('SDO Supply Officer can update a school NEC inside their division', function () {
    $response = updateSchoolNecAs(
        sdoSchoolNecUser(SDO_SUPPLY_OFFICER_TYPE_ID, 'division-1'),
        425
    );

    expect($response->getStatusCode())->toBe(200)
        ->and(DB::table('school_libraries')->where('id', 'library-1')->value('estimated_resource'))->toBe(425);
});

test('non SDO users cannot update a school NEC', function () {
    $response = updateSchoolNecAs(
        sdoSchoolNecUser('another-user-type', 'division-1'),
        425
    );

    expect($response->getStatusCode())->toBe(403)
        ->and(DB::table('school_libraries')->where('id', 'library-1')->value('estimated_resource'))->toBe(0);
});

test('SDO Supply Officer cannot update a school outside their division', function () {
    $response = updateSchoolNecAs(
        sdoSchoolNecUser(SDO_SUPPLY_OFFICER_TYPE_ID, 'division-2'),
        425
    );

    expect($response->getStatusCode())->toBe(403)
        ->and(DB::table('school_libraries')->where('id', 'library-1')->value('estimated_resource'))->toBe(0);
});
