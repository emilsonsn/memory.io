<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('users', [UserController::class, 'store'])->name('users.store');

Route::middleware('auth')->group(function (): void {
    Route::apiResource('plans', PlanController::class)->middleware('role:admin');
    Route::apiResource('users', UserController::class)->except('store')->middleware('role:admin');
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('memories', MemoryController::class);
});
