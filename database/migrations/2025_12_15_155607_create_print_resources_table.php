<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('print_title_id');
            $table->uuid('print_type_id');
            $table->string('publisher');
            $table->string('volume');
            $table->string('edition');
            $table->string('copyright');
            $table->integer('pages');
            $table->string('isbn');
            $table->string('subject_grade_level_ids');
            $table->timestamps();

            $table->foreign('print_title_id')->references('id')->on('print_titles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_resources');
    }
};
