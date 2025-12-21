<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('masterlist_id');
            $table->string('status');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('histories');
    }
};
