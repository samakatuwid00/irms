<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schools', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('school_name');
            $table->string('shortname');
            $table->string('school_type');
            $table->string('address');
            $table->string('contact_number');
            $table->string('email');
            $table->date('date_establish');
            $table->string('school_id');
            $table->string('legislative_district');
            $table->uuid('district_id');
            $table->timestamps();

            $table->foreign('district_id')->references('id')->on('districts');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schools');
    }
};
