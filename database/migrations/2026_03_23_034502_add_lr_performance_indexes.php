<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('print_acquisitions', function (Blueprint $table) {
            $table->index('library_id', 'print_acquisitions_library_id_idx');
            $table->index(['print_id', 'library_id'], 'print_acquisitions_print_id_library_id_idx');
        });

        DB::statement(
            "CREATE INDEX print_resources_sgl_ids_gin_idx
            ON print_resources USING gin (
                string_to_array(subject_grade_level_ids, ',')
            )"
        );

        DB::statement(
            "CREATE INDEX nonprint_resources_sgl_ids_gin_idx
            ON nonprint_resources USING gin (
                string_to_array(subject_grade_level_ids, ',')
            )"
        );

        Schema::table('populations', function (Blueprint $table) {
            $table->index('school_id', 'populations_school_id_idx');
        });

        Schema::table('school_libraries', function (Blueprint $table) {
            $table->index('school_id', 'school_libraries_school_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('print_acquisitions', function (Blueprint $table) {
            $table->dropIndex('print_acquisitions_library_id_idx');
            $table->dropIndex('print_acquisitions_print_id_library_id_idx');
        });

        DB::statement('DROP INDEX IF EXISTS print_resources_sgl_ids_gin_idx');
        DB::statement('DROP INDEX IF EXISTS nonprint_resources_sgl_ids_gin_idx');

        Schema::table('populations', function (Blueprint $table) {
            $table->dropIndex('populations_school_id_idx');
        });

        Schema::table('school_libraries', function (Blueprint $table) {
            $table->dropIndex('school_libraries_school_id_idx');
        });
    }
};
