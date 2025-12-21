<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subject_grade_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->uuid('subject_id');
            $table->uuid('grade_level_id');
            $table->string('school_type'); // Elementary, JHS, SHS
            $table->uuid('curriculum_id');

            $table->timestamps();

            $table->foreign('subject_id')
                  ->references('id')->on('subjects')
                  ->cascadeOnDelete();

            $table->foreign('grade_level_id')
                  ->references('id')->on('grade_levels')
                  ->cascadeOnDelete();

            $table->foreign('curriculum_id')
                  ->references('id')->on('curriculums')
                  ->cascadeOnDelete();

            $table->unique([
                'subject_id',
                'grade_level_id',
                'curriculum_id',
                'school_type'
            ], 'subject_grade_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subject_grade_levels');
    }
};
