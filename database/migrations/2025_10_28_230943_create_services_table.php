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
            $table->foreignId('queue_id')->nullable()->constrained('queues')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->onDelete('set null');
            $table->text('description')->nullable();
            $table->enum('status', ['waiting', 'process', 'done', 'cancelled'])->default('waiting');
            $table->dateTime('preferred_datetime')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('queues', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services');
        Schema::table('queues', function (Blueprint $table) {
            $table->dropColumn('service_id');
        });
    }
};
