<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/', [DashboardController::class, 'index']);
Route::get('/fetch-data', [DashboardController::class, 'fetchData'])->name('fetch.data');
Route::post('/toggle-status', [DashboardController::class, 'toggleStatus'])->name('toggle.status');
Route::get('/check-status', [App\Http\Controllers\DashboardController::class, 'checkStatus']);

Route::get('/fuzzy-rules', [DashboardController::class, 'fuzzyRules'])->name('fuzzy.rules');

// Route untuk menyimpan data baru (POST)
Route::post('/fuzzy-rules', [DashboardController::class, 'storeRule']);

// Route untuk menghapus data (DELETE)
Route::delete('/fuzzy-rules/{id}', [DashboardController::class, 'deleteRule']);

// Route untuk mengupdate data (PUT)
Route::put('/fuzzy-rules/{id}', [DashboardController::class, 'updateRule']);

Route::get('/export-log', [DashboardController::class, 'exportExcel'])->name('export.log');