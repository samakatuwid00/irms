<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_libraries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('school_id');
            $table->uuid('librarian');

            $table->foreign('school_id')->references('id')->on('schools');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_libraries');
    }
};
