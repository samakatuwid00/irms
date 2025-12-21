<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('division_name');
            $table->string('address');
            $table->string('contact_number');
            $table->string('email');
            $table->date('date_establish');
            $table->string('legislative_district');
            $table->uuid('region_id');
            $table->timestamps();

            $table->foreign('region_id')->references('id')->on('regions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divisions');
    }
};
