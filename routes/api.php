<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SensorController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FuzzyController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/send-data', [SensorController::class, 'store']);
Route::post('/send-data', [DashboardController::class, 'store']);
Route::get('/check-status', [DashboardController::class, 'checkStatus']);
Route::post('/send-data', [FuzzyController::class, 'hitungFuzzy']);