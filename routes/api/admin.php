<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\ServiceQueueController;
use App\Http\Controllers\ShopSessionController;
use App\Http\Controllers\QueueManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ServiceWorkflowController;

Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::post('users/{user}/assign-admin', [UserController::class, 'assignAdmin']);
    Route::post('users/{user}/assign-mechanic', [UserController::class, 'assignMechanic']);

    // Route::prefix('users')->group(function () {
    //     // Route::post('/{id}', [UserController::class, 'update']); // For multipart/form-data
    //     Route::put('/{id}', [UserController::class, 'update']); // For JSON
    // });
    // Route::prefix('products')->group(function () {
    //     // Route::post('/{id}', [ProductController::class, 'update']);
    //     Route::put('/{id}', [ProductController::class, 'update']);
    // });
    // Route::prefix('mechanics')->group(function () {
    //     // Route::post('/{id}', [MechanicController::class, 'update']);
    //     Route::put('/{id}', [MechanicController::class, 'update']);
    // });
    // Route::prefix('vehicles')->group(function () {
    //     // Route::post('/{id}', [VehicleController::class, 'update']);
    //     Route::put('/{vehicle}', [VehicleController::class, 'update']);
    // });

    Route::apiResource('users', UserController::class);
    Route::apiResource('mechanics', MechanicController::class);
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('products', ProductController::class);
    Route::apiResource('vehicles', VehicleController::class);

    // Service Management
    Route::apiResource('services', ServiceController::class)->except(['update']);
    Route::post('services/{service}/start', [ServiceController::class, 'start']);
    Route::post('services/{service}/status', [ServiceController::class, 'changeStatus']);
    Route::post('/services/{service}/approve', [ServiceController::class, 'approve']);
    Route::post('/services/{service}/cancel', [ServiceController::class, 'cancel']);
    Route::post('/services/{service}/complete', [ServiceWorkflowController::class, 'completeService']);
    Route::post('/services/{service}/details', [ServiceWorkflowController::class, 'addServiceDetail']);
    Route::get('/services/{service}/receipt', [ServiceWorkflowController::class, 'generateReceipt']);
    Route::delete('/services/{service}/details/{detail}', [ServiceWorkflowController::class, 'removeServiceDetail']);

    // 🆕 Queue Management
    Route::apiResource('queues', ServiceQueueController::class)->only(['index', 'show']);

    // 🆕 Phase 2: Shop Operations
    Route::prefix('shop')->group(function () {
        Route::post('/open', [ShopSessionController::class, 'openShop']);
        Route::post('/close', [ShopSessionController::class, 'closeShop']);
        Route::get('/status', [ShopSessionController::class, 'getStatus']);
    });

    // 🆕 Phase 2: Queue Management
    Route::prefix('queue')->group(function () {
        Route::get('/today', [QueueManagementController::class, 'getTodayQueues']);
        Route::post('/work-next', [QueueManagementController::class, 'workNextQueue']);
        Route::post('/{service}/requeue', [QueueManagementController::class, 'reQueueService']);
        Route::get('/statistics', [QueueManagementController::class, 'getQueueStatistics']);
    });

    // 🆕 Phase 2: Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('/overview', [DashboardController::class, 'getOverview']);
        Route::get('/revenue', [DashboardController::class, 'getRevenueStatistics']);
        Route::get('/services', [DashboardController::class, 'getServiceStatistics']);
        Route::get('/mechanics', [DashboardController::class, 'getMechanicPerformance']);
    });

    // Shop Sessions History
    Route::get('shop-sessions', [ShopSessionController::class, 'listSessions']);
});
