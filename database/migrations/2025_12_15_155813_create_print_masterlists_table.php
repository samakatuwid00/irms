<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_masterlists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('print_acquisition_id');
            $table->string('status');

            $table->foreign('print_acquisition_id')->references('id')->on('print_acquisitions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_masterlists');
    }
};
