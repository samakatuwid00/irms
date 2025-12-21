<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('populations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('male');
            $table->integer('female');
            $table->integer('total');
            $table->uuid('sy_id');
            $table->uuid('grade_id');
            $table->uuid('encoded_by');
            $table->timestamps();

            $table->foreign('sy_id')->references('id')->on('school_years');
            $table->foreign('grade_id')->references('id')->on('grades');
            $table->foreign('encoded_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};
