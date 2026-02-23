<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('district_name');
            $table->string('shortname')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->date('date_establish')->nullable();
            $table->string('legislative_district')->nullable();
            $table->uuid('division_id');
            $table->timestamps();
            $table->string('logo')->nullable();

            $table->foreign('division_id')->references('id')->on('divisions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('districts');
    }
};
