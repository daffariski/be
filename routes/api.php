<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerQueueController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\MechanicController;
use App\Http\Controllers\MechanicQueueController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\ServiceApprovalController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API of ' . config('app.name') . '!',
    ]);
});

// Public routes for authentication
Route::post('/login', [AuthController::class, 'login']);
Route::get('/auth/google/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('/catalog', [ProductController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/me', [AuthController::class, 'user']);

    // ----------------------------
    // Admin Routes
    // ----------------------------
    Route::middleware('role:admin')->prefix('admin')->group(function () {
        Route::post('users/{user}/assign-admin', [UserController::class, 'assignAdmin']);
        Route::post('users/{user}/assign-mechanic', [UserController::class, 'assignMechanic']);

        Route::prefix('users')->group(function () {
            Route::post('/{id}', [UserController::class, 'update']);
        });
        Route::prefix('products')->group(function () {
            Route::post('/{id}', [ProductController::class, 'update']);
        });
        Route::prefix('mechanics')->group(function () {
            Route::post('/{id}', [MechanicController::class, 'update']);
        });

        Route::apiResource('users', UserController::class)->except(['update']);
        Route::apiResource('mechanics', MechanicController::class)->except(['update']);
        Route::apiResource('customers', CustomerController::class)->except(['update']);
        Route::apiResource('products', ProductController::class)->except(['update']);
        Route::apiResource('vehicles', VehicleController::class)->except(['update']);

        // Service Management
        Route::apiResource('services', ServiceController::class)->except(['update']);
        Route::post('services/{service}/status', [ServiceController::class, 'changeStatus']);
        Route::post('/services/{service}/approve', [ServiceController::class, 'approve']);
        Route::post('/services/{service}/cancel', [ServiceController::class, 'cancel']);

        // 🆕 Queue Management
        Route::apiResource('queues', QueueController::class)->only(['index', 'show']);
    });

    // ----------------------------
    // Customer Routes
    // ----------------------------
    Route::prefix('customer')->middleware('role:customer')->group(function () {
        // Vehicle
        Route::get('vehicles', [VehicleController::class, 'customerVehicles']);
        Route::post('vehicles/{vehicle}', [VehicleController::class, 'updateCustomerVehicle']);

        // Service
        Route::get('services', [ServiceController::class, 'customerServices']);
        Route::post('services', [ServiceController::class, 'storeCustomerService']);

        // 🆕 Queue status tracking
        Route::get('queues', [CustomerQueueController::class, 'index']); // customer’s active queues
    });

    // ----------------------------
    // Mechanic Routes
    // ----------------------------
    Route::prefix('mechanic')->group(function () {
        Route::get('/assignments', [MechanicController::class, 'assignments']);
        Route::get('/queues', [MechanicController::class, 'queues']);
        Route::post('/queues/{id}', [MechanicController::class, 'updateQueue']);
    });

    // ----------------------------
    // General Routes
    // ----------------------------
    Route::prefix('options')->group(function () {
        Route::get('mechanic', [\App\Http\Controllers\MechanicController::class, 'getMechanicOptions']);
        Route::get('vehicle', [\App\Http\Controllers\VehicleController::class, 'allVehiclesOption']);
    });
});
