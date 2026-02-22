<?php

use App\Http\Controllers\Auth\GoogleController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return response()->json([
        'message' => 'Welcome to the API of ' . config('app.name') . '!',
    ]);
});

// Public routes for authentication
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::get('/auth/google/redirect', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);
Route::get('/catalog', [ProductController::class, 'index']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/me', [AuthController::class, 'user']);

    // ----------------------------
    // General Routes
    // ----------------------------
    Route::prefix('options')->group(function () {
        Route::get('mechanic', [\App\Http\Controllers\MechanicController::class, 'getMechanicOptions']);
        Route::get('vehicle', [\App\Http\Controllers\VehicleController::class, 'allVehiclesOption']);
        Route::get('product', [\App\Http\Controllers\ProductController::class, 'getProductOptions']);
        Route::get('customer', [\App\Http\Controllers\CustomerController::class, 'getCustomerOptions']);
    });
});

// ----------------------------
// Role-Based Routes (Organized in separate files)
// ----------------------------
require __DIR__ . '/api/admin.php';
require __DIR__ . '/api/customer.php';
require __DIR__ . '/api/mechanic.php';
