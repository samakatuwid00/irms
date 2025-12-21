<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('firstname');
            $table->string('middlename')->nullable();
            $table->string('lastname');
            $table->string('extension_name')->nullable();
            $table->string('gender');
            $table->date('birthday');
            $table->string('username');
            $table->string('password');
            $table->string('email');
            $table->string('contact_number');
            $table->string('photo')->nullable();
            $table->uuid('usertype_id');
            $table->uuid('station_id');
            $table->string('status');
            $table->uuid('approved_by');
            $table->timestamps();

            $table->foreign('usertype_id')->references('id')->on('usertypes');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
