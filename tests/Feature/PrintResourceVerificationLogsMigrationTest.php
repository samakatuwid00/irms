<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

test('verification log migration adopts an existing table without duplicating history', function () {
    Schema::disableForeignKeyConstraints();

    foreach (['print_resource_verification_logs', 'print_resources', 'users', 'usertypes'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::enableForeignKeyConstraints();

    Schema::create('usertypes', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->unsignedTinyInteger('level');
        $table->string('type_name');
    });

    Schema::create('users', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('usertype_id')->nullable();
    });

    Schema::create('print_resources', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->boolean('verified')->default(false);
        $table->uuid('verified_by')->nullable();
        $table->timestamp('verified_at')->nullable();
    });

    Schema::create('print_resource_verification_logs', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('print_resource_id');
        $table->uuid('user_id')->nullable();
        $table->unsignedTinyInteger('user_level')->nullable();
        $table->string('user_role')->nullable();
        $table->string('action_type', 50);
        $table->text('comment')->nullable();
        $table->json('previous_metadata')->nullable();
        $table->json('new_metadata')->nullable();
        $table->timestamp('created_at')->useCurrent();
    });

    $userTypeId = (string) Str::uuid();
    $userId = (string) Str::uuid();
    $alreadyLoggedResourceId = (string) Str::uuid();
    $missingLogResourceId = (string) Str::uuid();

    DB::table('usertypes')->insert([
        'id' => $userTypeId,
        'level' => 3,
        'type_name' => 'Division',
    ]);

    DB::table('users')->insert([
        'id' => $userId,
        'usertype_id' => $userTypeId,
    ]);

    DB::table('print_resources')->insert([
        [
            'id' => $alreadyLoggedResourceId,
            'verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ],
        [
            'id' => $missingLogResourceId,
            'verified' => true,
            'verified_by' => $userId,
            'verified_at' => now(),
        ],
    ]);

    DB::table('print_resource_verification_logs')->insert([
        'id' => (string) Str::uuid(),
        'print_resource_id' => $alreadyLoggedResourceId,
        'user_id' => $userId,
        'user_level' => 3,
        'user_role' => 'Division',
        'action_type' => 'first_verification',
        'created_at' => now(),
    ]);

    $migration = require database_path('migrations/2026_06_24_000001_create_print_resource_verification_logs_table.php');
    $migration->up();
    $migration->up();

    expect(DB::table('print_resource_verification_logs')->count())->toBe(2)
        ->and(DB::table('print_resource_verification_logs')
            ->where('print_resource_id', $alreadyLoggedResourceId)
            ->where('action_type', 'first_verification')
            ->count())->toBe(1)
        ->and(DB::table('print_resource_verification_logs')
            ->where('print_resource_id', $missingLogResourceId)
            ->where('action_type', 'first_verification')
            ->count())->toBe(1);
});
