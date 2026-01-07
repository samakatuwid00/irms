<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_titles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_titles');
    }
};
