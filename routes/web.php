<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json(['message' => 'Support Board API is running']);
});

Route::get('/health', function () {
    return response()->json(['status' => 'ok', 'timestamp' => now()]);
});

// Installer (public, runs once) - web routes for the HTML frontend
Route::get('/installer', [App\Http\Controllers\InstallerController::class, 'index'])->name('installer.index');
Route::get('/installer/check', [App\Http\Controllers\InstallerController::class, 'check']);
Route::post('/installer/test-db', [App\Http\Controllers\InstallerController::class, 'testDb']);
Route::post('/installer/run', [App\Http\Controllers\InstallerController::class, 'run']);
