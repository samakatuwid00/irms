<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonprint_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nonprint_title_id');
            $table->uuid('nonprint_type_id');
            $table->string('brand');
            $table->string('code');
            $table->string('version');
            $table->string('url');
            $table->string('size');
            $table->string('model');
            $table->string('subject_grade_level_ids');
            $table->timestamps();
            $table->uuid('library_id');

            $table->foreign('nonprint_title_id')->references('id')->on('nonprint_titles');
            $table->foreign('nonprint_type_id')->references('id')->on('nonprint_types');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonprint_resources');
    }
};
