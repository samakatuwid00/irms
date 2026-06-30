<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_resources', function (Blueprint $table) {
            $table->text('subject_grade_level_ids')->change();
        });
    }

    public function down(): void
    {
        Schema::table('print_resources', function (Blueprint $table) {
            $table->string('subject_grade_level_ids')->change();
        });
    }
};
