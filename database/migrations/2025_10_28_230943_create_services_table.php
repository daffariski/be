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
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->cascadeOnDelete();
            $table->string('customer_name')->nullable();
            $table->foreignId('mechanic_id')->nullable()->constrained('mechanics')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->foreignId('vehicle_id')->constrained()->restrictOnDelete();
            $table->text('description')->nullable();

            // Queue management fields
            $table->date('queue_date')->nullable()->comment('Which day this service is queued for');
            $table->integer('queue_priority')->default(999)->comment('Manual priority override (lower = higher priority)');
            $table->timestamp('queued_at')->nullable()->comment('When service was added to queue (never changes after set)');

            // Status and workflow
            $table->enum('status', ['waiting', 'process', 'done', 'cancelled'])->default('waiting');

            // Payment fields (moved from separate table for simplicity)
            $table->unsignedBigInteger('service_fee')->default(0)->comment('service fee without products');
            $table->unsignedBigInteger('product_fee')->default(0)->comment('fee for products used');
            $table->unsignedBigInteger('total_price')->default(0)->comment('Total service cost');
            $table->string('payment_method')->nullable()->comment('cash, qris, transfer, etc');
            $table->string('payment_proof')->nullable()->comment('File path for payment proof (cashless)');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');

            $table->timestamp('started_at')->nullable()->comment('When mechanic started working on this service');
            $table->timestamp('finished_at')->nullable()->comment('When mechanic finished working on this service');
            // $table->string('cancel_reason')->nullable()->comment('Reason for cancellation, if status is cancelled');
            $table->timestamp('cancelled_at')->nullable()->comment('When service was cancelled');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
