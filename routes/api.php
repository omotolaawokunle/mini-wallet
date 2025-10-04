<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TransactionController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store'])->middleware('throttle:3,1');

    // Broadcasting authentication
    Route::post('/broadcasting/auth', function () {
        return \Illuminate\Support\Facades\Auth::user();
    });
});
