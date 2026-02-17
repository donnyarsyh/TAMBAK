<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [DashboardController::class, 'index']);
Route::get('/fetch-data', [DashboardController::class, 'fetchData'])->name('fetch.data');
Route::post('/toggle-status', [DashboardController::class, 'toggleStatus'])->name('toggle.status');