<?php

use App\Enums\UserRole;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('users', [UserController::class, 'store'])->name('users.store');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::post('login', [AuthController::class, 'login'])->name('login');

    Route::middleware('auth:api')->group(function (): void {
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::post('refresh', [AuthController::class, 'refresh'])->name('refresh');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');
    });
});

Route::middleware('auth:api')->group(function (): void {

    Route::prefix('notifications')->name('notifications.')->group(function (): void {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::patch('read', [NotificationController::class, 'readMany'])->name('read-many');
        Route::patch('{notification}/read', [NotificationController::class, 'read'])->name('read');
    });

    Route::prefix('memories')->name('memories.')->group(function (): void {
        Route::get('{memory}/logs', [MemoryController::class, 'logs'])->name('logs');
        Route::get('{memory}/export', [MemoryController::class, 'export'])->name('export');
    });

    Route::apiResource('plans', PlanController::class)->middleware('role:'.UserRole::ADMIN->value);
    Route::apiResource('users', UserController::class)->except('store')->middleware('role:'.UserRole::ADMIN->value);
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('memories', MemoryController::class);
});
