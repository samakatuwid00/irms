<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grade_offerings', function (Blueprint $table) {
            $table->enum('ng', ['yes', 'no'])->default('no')->after('g12');
        });

        Schema::table('populations', function (Blueprint $table) {
            $table->unsignedInteger('ng_m')->default(0)->after('g12_total');
            $table->unsignedInteger('ng_f')->default(0)->after('ng_m');
            $table->unsignedInteger('ng_total')->default(0)->after('ng_f');
        });
    }

    public function down(): void
    {
        Schema::table('grade_offerings', function (Blueprint $table) {
            $table->dropColumn('ng');
        });

        Schema::table('populations', function (Blueprint $table) {
            $table->dropColumn(['ng_m', 'ng_f', 'ng_total']);
        });
    }
};