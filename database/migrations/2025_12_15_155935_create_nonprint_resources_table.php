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
            $table->timestamps();

            $table->foreign('nonprint_title_id')->references('id')->on('nonprint_titles');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonprint_resources');
    }
};
