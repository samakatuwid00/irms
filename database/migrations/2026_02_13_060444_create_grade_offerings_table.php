<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grade_offerings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->enum('K', ['yes', 'no'])->default('no');
            $table->enum('g1', ['yes', 'no'])->default('no');
            $table->enum('g2', ['yes', 'no'])->default('no');
            $table->enum('g3', ['yes', 'no'])->default('no');
            $table->enum('g4', ['yes', 'no'])->default('no');
            $table->enum('g5', ['yes', 'no'])->default('no');
            $table->enum('g6', ['yes', 'no'])->default('no');
            $table->enum('g7', ['yes', 'no'])->default('no');
            $table->enum('g8', ['yes', 'no'])->default('no');
            $table->enum('g9', ['yes', 'no'])->default('no');
            $table->enum('g10', ['yes', 'no'])->default('no');
            $table->enum('g11', ['yes', 'no'])->default('no');
            $table->enum('g12', ['yes', 'no'])->default('no');
            $table->timestamps();

            $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_offerings');
    }
};

