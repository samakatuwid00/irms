<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('print_resource_sgl', function (Blueprint $table) {
            $table->uuid('print_id');
            $table->uuid('sgl_id');

            $table->index('print_id');
            $table->index('sgl_id');

            $table->foreign('print_id')->references('id')->on('print_resources')->cascadeOnDelete();
            $table->foreign('sgl_id')->references('id')->on('subject_grade_levels')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('print_resource_sgl');
    }
};
