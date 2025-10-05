<?php

use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

// Broadcasting authentication - requires Sanctum auth
Route::post('/broadcasting/auth', function () {
    return Broadcast::auth(request());
})->middleware('auth:sanctum');

Route::get('/', function () {
    return view('welcome');
});
