<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\DashboardController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/send-data', [SensorController::class, 'store']);
// Route kirim data yang kemarin juga pastikan di sini
Route::post('/send-data', [DashboardController::class, 'store']);
Route::get('/check-status', [DashboardController::class, 'checkStatus']);