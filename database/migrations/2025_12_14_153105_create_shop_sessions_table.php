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
        Schema::create('shop_sessions', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique()->comment('Session date (one per day)');
            $table->timestamp('opened_at')->nullable()->comment('When shop was opened');
            $table->timestamp('closed_at')->nullable()->comment('When shop was closed (auto at 5 PM or manual)');
            $table->enum('status', ['open', 'closed'])->default('closed');

            // Daily statistics
            $table->integer('services_completed')->default(0)->comment('Services marked as done today');
            $table->integer('services_cancelled')->default(0)->comment('Services cancelled today');
            $table->unsignedBigInteger('gross_revenue')->default(0)->comment('Total revenue for the day');

            // Additional metadata
            $table->foreignId('opened_by')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who opened shop');
            $table->foreignId('closed_by')->nullable()->constrained('admins')->nullOnDelete()->comment('Admin who closed shop');
            $table->text('notes')->nullable()->comment('Any notes for the day');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_sessions');
    }
};
