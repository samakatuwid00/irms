<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('print_acquisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('library_id');
            $table->uuid('print_id');
            $table->string('source');
            $table->date('date_acquired');
            $table->decimal('cost', 8, 2);
            $table->string('iar');
            $table->integer('total_qty');
            $table->integer('usable');
            $table->integer('partially_damaged');
            $table->integer('damaged');
            $table->integer('lost');
            $table->integer('condemnable');
            $table->text('remarks');
            $table->date('date_encoded');
            $table->uuid('encoded_by');
            $table->uuid('curriculum_id');
            $table->timestamps();

            $table->foreign('print_id')->references('id')->on('print_resources');
            $table->foreign('encoded_by')->references('id')->on('users');
            $table->foreign('curriculum_id')->references('id')->on('curriculums');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_acquisitions');
    }
};
