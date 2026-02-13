<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('populations', function (Blueprint $table) {

            $table->uuid('id')->primary();

            $table->uuid('school_id');
            $table->uuid('sy_id');

            // Kindergarten
            $table->integer('k_m')->default(0);
            $table->integer('k_f')->default(0);
            $table->integer('k_total')->default(0);

            // Grade 1
            $table->integer('g1_m')->default(0);
            $table->integer('g1_f')->default(0);
            $table->integer('g1_total')->default(0);

            // Grade 2
            $table->integer('g2_m')->default(0);
            $table->integer('g2_f')->default(0);
            $table->integer('g2_total')->default(0);

            // Grade 3
            $table->integer('g3_m')->default(0);
            $table->integer('g3_f')->default(0);
            $table->integer('g3_total')->default(0);

            // Grade 4
            $table->integer('g4_m')->default(0);
            $table->integer('g4_f')->default(0);
            $table->integer('g4_total')->default(0);

            // Grade 5
            $table->integer('g5_m')->default(0);
            $table->integer('g5_f')->default(0);
            $table->integer('g5_total')->default(0);

            // Grade 6
            $table->integer('g6_m')->default(0);
            $table->integer('g6_f')->default(0);
            $table->integer('g6_total')->default(0);

            // Grade 7
            $table->integer('g7_m')->default(0);
            $table->integer('g7_f')->default(0);
            $table->integer('g7_total')->default(0);

            // Grade 8
            $table->integer('g8_m')->default(0);
            $table->integer('g8_f')->default(0);
            $table->integer('g8_total')->default(0);

            // Grade 9
            $table->integer('g9_m')->default(0);
            $table->integer('g9_f')->default(0);
            $table->integer('g9_total')->default(0);

            // Grade 10
            $table->integer('g10_m')->default(0);
            $table->integer('g10_f')->default(0);
            $table->integer('g10_total')->default(0);

            // Grade 11
            $table->integer('g11_m')->default(0);
            $table->integer('g11_f')->default(0);
            $table->integer('g11_total')->default(0);

            // Grade 12
            $table->integer('g12_m')->default(0);
            $table->integer('g12_f')->default(0);
            $table->integer('g12_total')->default(0);

            $table->uuid('encoded_by');

            $table->timestamps();

            // Foreign Keys
            $table->foreign('school_id')
                  ->references('id')
                  ->on('schools')
                  ->onDelete('cascade');

            $table->foreign('sy_id')
                  ->references('id')
                  ->on('school_years')
                  ->onDelete('cascade');

            $table->foreign('encoded_by')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            // Prevent duplicate school per school year
            $table->unique(['school_id', 'sy_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('populations');
    }
};

