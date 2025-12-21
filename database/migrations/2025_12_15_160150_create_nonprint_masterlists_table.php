<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nonprint_masterlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('nonprint_acquisition_id');
            $table->string('status');

            $table->foreign('nonprint_acquisition_id')->references('id')->on('nonprint_acquisitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nonprint_masterlists');
    }
};
