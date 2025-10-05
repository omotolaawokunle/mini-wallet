<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ValidateReceiver;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store'])->middleware('throttle:3,1');
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/validate-receiver', ValidateReceiver::class);
});
