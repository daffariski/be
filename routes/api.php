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

    Route::middleware('role:admin')->group(function () {
        // User Management
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/assign-admin', [UserController::class, 'assignAdmin']);
        Route::post('users/{user}/assign-mechanic', [UserController::class, 'assignMechanic']);

        // Customer & Product Management
        Route::apiResource('customers', CustomerController::class);
        Route::apiResource('products', ProductController::class);
        Route::apiResource('vehicles', VehicleController::class);

        // Service Management
        Route::apiResource('services', ServiceController::class);
        Route::patch('services/{service}/status', [ServiceController::class, 'changeStatus']);
        Route::patch('/services/{service}/approve', [ServiceController::class, 'approve']);
        Route::patch('/services/{service}/cancel', [ServiceController::class, 'cancel']);

        // 🆕 Queue Management
        Route::apiResource('queues', QueueController::class)->only(['index', 'show']);
    });


    // ----------------------------
    // Customer Routes
    // ----------------------------
    Route::prefix('customer')->middleware('role:customer')->group(function () {
        // Vehicle
        Route::get('vehicles', [VehicleController::class, 'customerVehicles']);
        Route::patch('vehicles/{vehicle}', [VehicleController::class, 'updateCustomerVehicle']);

        // Service
        Route::get('services', [ServiceController::class, 'customerServices']);
        Route::post('services', [ServiceController::class, 'storeCustomerService']);

        // 🆕 Queue status tracking
        Route::get('queues', [CustomerQueueController::class, 'index']); // customer’s active queues
    });

     // ----------------------------
    // Mechanic Routes
    // ----------------------------
    Route::middleware(['auth:sanctum'])->prefix('mechanic')->group(function () {
    Route::get('/assignments', [MechanicController::class, 'assignments']);
    Route::get('/queues', [MechanicController::class, 'queues']);
    Route::patch('/queues/{id}', [MechanicController::class, 'updateQueue']);
});

    // ----------------------------
    // General Routes
    // ----------------------------
    Route::prefix('options')->group(function () {
        Route::get('mechanic', [\App\Http\Controllers\MechanicController::class, 'getMechanicOptions']);
        Route::get('vehicle', [\App\Http\Controllers\VehicleController::class, 'allVehiclesOption']);
    });
});
