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
    // View assigned services (in process)
    Route::get('services', [ServiceWorkflowController::class, 'getMechanicActiveServices']);
    // Completed services history
    Route::get('services/history', [ServiceWorkflowController::class, 'getMechanicCompletedServices']);
    // Finish a service (mark done)
    Route::post('services/{service}/finish', [ServiceWorkflowController::class, 'finishService']);

    // ===============================================
    // Service Details (Parts Used)
    // ===============================================
    Route::post('services/{service}/details', [ServiceWorkflowController::class, 'addServiceDetail']);
    Route::delete('services/{service}/details/{detail}', [ServiceWorkflowController::class, 'removeServiceDetail']);
});
