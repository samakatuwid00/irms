<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('author_nonprint_title', function (Blueprint $table) {
            $table->uuid('author_id');
            $table->uuid('nonprint_title_id');

            $table->primary(['author_id', 'nonprint_title_id']);

            $table->foreign('author_id')->references('id')->on('authors')->cascadeOnDelete();
            $table->foreign('nonprint_title_id')->references('id')->on('nonprint_titles')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('author_nonprint_title');
    }
};
