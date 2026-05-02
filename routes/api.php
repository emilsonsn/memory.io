<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MemoryController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('users', [UserController::class, 'store'])->name('users.store');

Route::middleware('auth')->group(function (): void {
    Route::apiResource('users', UserController::class)->except('store');
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('memories', MemoryController::class);
});
