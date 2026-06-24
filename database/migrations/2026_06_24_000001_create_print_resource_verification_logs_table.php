<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_resource_verification_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('print_resource_id');
            $table->uuid('user_id')->nullable();
            $table->unsignedTinyInteger('user_level')->nullable();
            $table->string('user_role')->nullable();
            $table->string('action_type', 50);
            $table->text('comment')->nullable();
            $table->json('previous_metadata')->nullable();
            $table->json('new_metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('print_resource_id')
                ->references('id')
                ->on('print_resources')
                ->cascadeOnDelete();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->index(['print_resource_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action_type');
        });

        $this->backfillExistingVerifications();
    }

    public function down(): void
    {
        Schema::dropIfExists('print_resource_verification_logs');
    }

    private function backfillExistingVerifications(): void
    {
        if (! Schema::hasColumn('print_resources', 'verified')) {
            return;
        }

        DB::table('print_resources')
            ->leftJoin('users', 'users.id', '=', 'print_resources.verified_by')
            ->leftJoin('usertypes', 'usertypes.id', '=', 'users.usertype_id')
            ->where('print_resources.verified', true)
            ->whereNotNull('print_resources.verified_by')
            ->orderBy('print_resources.id')
            ->select([
                'print_resources.id as print_resource_id',
                'print_resources.verified_by as user_id',
                'print_resources.verified_at as verified_at',
                'usertypes.level as user_level',
                'usertypes.type_name as user_role',
            ])
            ->chunkById(200, function ($rows) {
                $logs = [];

                foreach ($rows as $row) {
                    $timestamp = $row->verified_at ?? now();
                    $logs[] = [
                        'id' => (string) Str::uuid(),
                        'print_resource_id' => $row->print_resource_id,
                        'user_id' => $row->user_id,
                        'user_level' => $row->user_level,
                        'user_role' => $row->user_role,
                        'action_type' => 'first_verification',
                        'comment' => null,
                        'previous_metadata' => null,
                        'new_metadata' => null,
                        'created_at' => $timestamp,
                    ];
                }

                if (! empty($logs)) {
                    DB::table('print_resource_verification_logs')->insert($logs);
                }
            }, 'print_resources.id', 'print_resource_id');
    }
};
