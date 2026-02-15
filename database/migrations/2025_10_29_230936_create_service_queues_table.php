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
        Schema::create('service_queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->integer('queue_number')->comment('Sequential queue number for the day');
            $table->date('queue_date')->comment('Which day this queue is for');
            $table->enum('status', ['waiting', 'process', 'done', 'cancelled'])->default('waiting');
            $table->timestamps();

            // Ensure unique queue numbers per day
            $table->unique(['queue_date', 'queue_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_queues');
    }
};
