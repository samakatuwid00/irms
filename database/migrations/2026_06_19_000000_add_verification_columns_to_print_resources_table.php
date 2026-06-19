<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('print_resources', function (Blueprint $table) {
            $table->boolean('verified')->default(false)->after('status');
            $table->uuid('verified_by')->nullable()->after('verified');
            $table->timestamp('verified_at')->nullable()->after('verified_by');

            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['verified', 'verified_at']);
        });
    }

    public function down(): void
    {
        Schema::table('print_resources', function (Blueprint $table) {
            $table->dropForeign(['verified_by']);
            $table->dropColumn(['verified', 'verified_by', 'verified_at']);
        });
    }
};
