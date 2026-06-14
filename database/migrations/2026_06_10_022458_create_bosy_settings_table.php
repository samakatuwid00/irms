<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table stores the active BOSY period and calendar year,
     * set globally by the Regional Account and displayed to all users.
     */
    public function up(): void
    {
        Schema::create('bosy_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('calendar_year')->comment('e.g. 2025');
            $table->date('period_start')->comment('e.g. 2025-06-05');
            $table->date('period_end')->comment('e.g. 2025-12-25');
            $table->string('period_label', 60)->nullable()
                  ->comment('Human-readable override, e.g. "05 June – 25 Dec". Auto-generated if null.');
            $table->string('updated_by_user_id', 36)->nullable()
                  ->comment('UUID of the regional user who last updated');
            $table->timestamps();
        });

        // Seed one default row so the dashboard always has something to read
        DB::table('bosy_settings')->insert([
            'calendar_year' => (int) date('Y'),
            'period_start'  => date('Y') . '-06-05',
            'period_end'    => date('Y') . '-12-25',
            'period_label'  => null,
            'updated_by_user_id' => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bosy_settings');
    }
};