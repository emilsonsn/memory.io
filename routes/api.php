<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\MemoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function (): void {
    Route::apiResource('categories', CategoryController::class);
    Route::apiResource('memories', MemoryController::class);
});
