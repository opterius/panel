<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ServerController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Admin / Reseller only
    Route::middleware('admin')->group(function () {
        Route::resource('servers', ServerController::class)->except(['edit', 'update']);
        Route::resource('accounts', AccountController::class)->except(['edit', 'update']);
        Route::resource('packages', PackageController::class)->except(['show']);
    });

    // User routes (all authenticated users)
    Route::get('/domains', function () {
        return view('domains.index');
    })->name('domains.index');

    Route::get('/databases', function () {
        return view('databases.index');
    })->name('databases.index');

    Route::get('/ssl', function () {
        return view('ssl.index');
    })->name('ssl.index');

    Route::get('/cron-jobs', function () {
        return view('cronjobs.index');
    })->name('cronjobs.index');

    Route::get('/file-manager', function () {
        return view('filemanager.index');
    })->name('filemanager.index');
});
