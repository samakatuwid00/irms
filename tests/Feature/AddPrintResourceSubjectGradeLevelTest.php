<?php

use App\Http\Controllers\Resource\AddPrintResourceController;
use App\Services\Resource\Actions\AddPrintResourceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

beforeEach(function () {
    foreach ([
        'print_resource_sgl',
        'print_resources',
        'print_types',
        'subject_grade_levels',
        'grade_levels',
        'subjects',
        'key_stages',
    ] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('key_stages', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('code')->unique();
        $table->string('name');
        $table->unsignedTinyInteger('sort_order');
    });

    Schema::create('grade_levels', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('grade');
        $table->unsignedTinyInteger('sort_order');
        $table->uuid('key_stage_id');
    });

    Schema::create('subjects', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('subject_name');
    });

    Schema::create('subject_grade_levels', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->uuid('subject_id');
        $table->uuid('grade_level_id');
    });

    Schema::create('print_types', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('type_name');
    });

    Schema::create('print_resources', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('subject_grade_level_ids');
    });

    Schema::create('print_resource_sgl', function (Blueprint $table) {
        $table->uuid('print_id');
        $table->uuid('sgl_id');
    });
});

afterEach(function () {
    foreach ([
        'print_resource_sgl',
        'print_resources',
        'print_types',
        'subject_grade_levels',
        'grade_levels',
        'subjects',
        'key_stages',
    ] as $table) {
        Schema::dropIfExists($table);
    }
});

test('add print resource supplies canonical key stage codes to the checkbox table', function () {
    $stageId = (string) Str::uuid();
    $gradeId = (string) Str::uuid();
    $subjectId = (string) Str::uuid();
    $mappingId = (string) Str::uuid();

    DB::table('key_stages')->insert([
        'id' => $stageId,
        'code' => 'KS1',
        'name' => 'Key Stage 1 — Kindergarten to Grade 3',
        'sort_order' => 1,
    ]);
    DB::table('grade_levels')->insert([
        'id' => $gradeId,
        'grade' => 'Kindergarten',
        'sort_order' => 0,
        'key_stage_id' => $stageId,
    ]);
    DB::table('subjects')->insert([
        'id' => $subjectId,
        'subject_name' => 'Kindergarten Domains',
    ]);
    DB::table('subject_grade_levels')->insert([
        'id' => $mappingId,
        'subject_id' => $subjectId,
        'grade_level_id' => $gradeId,
    ]);

    $controller = new AddPrintResourceController(new AddPrintResourceService());
    $method = new ReflectionMethod($controller, 'getSubjectGradeLevels');
    $method->setAccessible(true);

    $mapping = $method->invoke($controller)->first();

    expect($mapping->key_stage)->toBe('KS1')
        ->and($mapping->grade_level)->toBe('Kindergarten')
        ->and($mapping->subject_name)->toBe('Kindergarten Domains')
        ->and($mapping->subject_grade_level_id)->toBe($mappingId);
});

test('add print resource rejects unknown and duplicate subject grade mappings', function () {
    $printTypeId = (string) Str::uuid();
    $mappingId = (string) Str::uuid();

    DB::table('print_types')->insert([
        'id' => $printTypeId,
        'type_name' => 'Textbook',
    ]);
    DB::table('subject_grade_levels')->insert([
        'id' => $mappingId,
        'subject_id' => (string) Str::uuid(),
        'grade_level_id' => (string) Str::uuid(),
    ]);

    $controller = new AddPrintResourceController(new AddPrintResourceService());
    $method = new ReflectionMethod($controller, 'resourceValidationRules');
    $method->setAccessible(true);
    $rules = $method->invoke($controller);
    $base = [
        'title' => 'Test Resource',
        'type' => $printTypeId,
    ];

    $valid = Validator::make(
        $base + ['subject_grade_levels' => [$mappingId]],
        $rules
    );
    $unknown = Validator::make(
        $base + ['subject_grade_levels' => [(string) Str::uuid()]],
        $rules
    );
    $duplicate = Validator::make(
        $base + ['subject_grade_levels' => [$mappingId, $mappingId]],
        $rules
    );

    expect($valid->passes())->toBeTrue()
        ->and($unknown->errors()->has('subject_grade_levels.0'))->toBeTrue()
        ->and($duplicate->errors()->has('subject_grade_levels.1'))->toBeTrue();
});

test('add print resource synchronizes selected mappings into the pivot table', function () {
    $resourceId = (string) Str::uuid();
    $firstMappingId = (string) Str::uuid();
    $secondMappingId = (string) Str::uuid();
    $service = new AddPrintResourceService();
    $method = new ReflectionMethod($service, 'syncSubjectGradeLevels');
    $method->setAccessible(true);

    $method->invoke(
        $service,
        $resourceId,
        [$firstMappingId, $firstMappingId, $secondMappingId]
    );

    expect(DB::table('print_resource_sgl')->where('print_id', $resourceId)->count())->toBe(2);

    $method->invoke($service, $resourceId, [$secondMappingId]);

    expect(
        DB::table('print_resource_sgl')
            ->where('print_id', $resourceId)
            ->pluck('sgl_id')
            ->all()
    )->toBe([$secondMappingId]);
});

test('print resource subject grade storage is expanded beyond varchar', function () {
    $migration = require database_path(
        'migrations/2026_06_30_000000_expand_print_resource_subject_grade_level_ids.php'
    );

    $migration->up();

    expect(Schema::getColumnType('print_resources', 'subject_grade_level_ids'))->toBe('text');
});
