<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('grade_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('grade');
            $table->string('classification')->nullable();
            $table->timestamps();
            $table->integer('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_levels');
    }
};
