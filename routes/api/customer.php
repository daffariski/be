<?php

use App\Http\Controllers\Customer\CustomerServiceController;
use App\Http\Controllers\Customer\CustomerVehicleController;
use App\Http\Controllers\Customer\CustomerProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerQueueController;
use App\Http\Controllers\QueueManagementController;

/**
 * Customer Routes
 * Protected by auth:sanctum and role:customer middleware
 * Prefix: /api/customer
 */

Route::middleware(['auth:sanctum', 'role:customer'])->prefix('customer')->group(function () {

    // ===============================================
    // Profile Management
    // ===============================================
    Route::get('profile', [CustomerProfileController::class, 'show']);
    Route::put('profile', [CustomerProfileController::class, 'update']);

    // ===============================================
    // Vehicle Management
    // ===============================================
    Route::get('vehicles', [CustomerVehicleController::class, 'customerVehicles']);
    Route::post('vehicles/{vehicle}', [CustomerVehicleController::class, 'updateCustomerVehicle']);

    // ===============================================
    // Service Management
    // ===============================================
    Route::get('services', [CustomerServiceController::class, 'customerServices']);
    Route::post('services', [CustomerServiceController::class, 'storeCustomerService']);

    // ===============================================
    // Queue Status Tracking
    // ===============================================
    Route::get('queues', [CustomerQueueController::class, 'index']); // customer's active queues
    Route::get('queue/position', [QueueManagementController::class, 'getCustomerQueuePosition']); // my queue position
});
