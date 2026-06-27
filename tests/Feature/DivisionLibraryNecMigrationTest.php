<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

test('division library NEC migration preserves the column through a rename', function () {
    $migrationPath = database_path('migrations/2026_06_27_120000_rename_division_library_estimated_resource_to_nec.php');

    if (! file_exists($migrationPath)) {
        $this->markTestSkipped('The division library NEC rename migration is not present.');
    }

    Schema::create('division_libraries', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->unsignedBigInteger('estimated_resource');
    });

    $migration = require $migrationPath;
    $migration->up();

    expect(Schema::hasColumn('division_libraries', 'net_expected_count'))->toBeTrue()
        ->and(Schema::hasColumn('division_libraries', 'estimated_resource'))->toBeFalse();
});
