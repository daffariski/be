<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\ServiceWorkflowController;

/**
 * Mechanic Routes
 * Protected by auth:sanctum and role:mechanic middleware
 * Prefix: /api/mechanic
 */

Route::middleware(['auth:sanctum', 'role:mechanic'])->prefix('mechanic')->group(function () {

    // ===============================================
    // Service Management
    // ===============================================
    // View assigned services
    Route::get('services', [ServiceWorkflowController::class, 'getMechanicActiveServices']);

    // ===============================================
    // Service Details (Parts Used)
    // ===============================================
    // Add/remove service details (parts used)
    Route::post('services/{service}/details', [ServiceWorkflowController::class, 'addServiceDetail']);
    Route::delete('services/{service}/details/{detail}', [ServiceWorkflowController::class, 'removeServiceDetail']);

    // ===============================================
    // Legacy Endpoints
    // ===============================================
    // Legacy endpoints (if still needed)
    Route::get('assignments', [MechanicController::class, 'assignments']);
    Route::get('queues', [MechanicController::class, 'queues']);
    Route::post('queues/{id}', [MechanicController::class, 'updateQueue']);
});
