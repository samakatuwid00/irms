<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('regions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('region_name');
            $table->string('shortname')->nullable();
            $table->string('address')->nullable();
            $table->string('contact_number')->nullable();
            $table->string('email')->nullable();
            $table->date('date_establish')->nullable();
            $table->string('logo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('regions');
    }
};
