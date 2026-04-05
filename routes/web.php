<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DomainController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\ServerController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\LicenseController;
use App\Http\Controllers\PhpController;
use App\Http\Controllers\SshController;
use App\Http\Controllers\SslController;
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

        // License (admin only)
        Route::get('/license', [LicenseController::class, 'index'])->name('license.index');
        Route::put('/license', [LicenseController::class, 'update'])->name('license.update');
        Route::post('/license/refresh', [LicenseController::class, 'refresh'])->name('license.refresh');
    });

    // User routes (all authenticated users)
    Route::resource('domains', DomainController::class)->only(['index', 'create', 'store', 'destroy']);

    // Databases
    Route::resource('databases', DatabaseController::class)->only(['index', 'create', 'store', 'destroy']);

    // SSL Certificates
    Route::get('/ssl', [SslController::class, 'index'])->name('ssl.index');
    Route::post('/ssl/issue', [SslController::class, 'issue'])->name('ssl.issue');
    Route::post('/ssl/upload', [SslController::class, 'upload'])->name('ssl.upload');
    Route::post('/ssl/{certificate}/renew', [SslController::class, 'renew'])->name('ssl.renew');
    Route::delete('/ssl/{certificate}', [SslController::class, 'destroy'])->name('ssl.destroy');

    // PHP Management
    Route::get('/php', [PhpController::class, 'index'])->name('php.index');
    Route::post('/php/install', [PhpController::class, 'install'])->name('php.install');
    Route::post('/php/switch', [PhpController::class, 'switchVersion'])->name('php.switch');
    Route::post('/php/config', [PhpController::class, 'config'])->name('php.config');

    // SSH Access
    Route::get('/ssh', [SshController::class, 'index'])->name('ssh.index');
    Route::post('/ssh/import-key', [SshController::class, 'importKey'])->name('ssh.import-key');
    Route::post('/ssh/delete-key', [SshController::class, 'deleteKey'])->name('ssh.delete-key');
    Route::post('/ssh/toggle-shell', [SshController::class, 'toggleShell'])->name('ssh.toggle-shell');

    // Cron Jobs
    Route::get('/cron-jobs', [CronJobController::class, 'index'])->name('cronjobs.index');
    Route::get('/cron-jobs/create', [CronJobController::class, 'create'])->name('cronjobs.create');
    Route::post('/cron-jobs', [CronJobController::class, 'store'])->name('cronjobs.store');
    Route::post('/cron-jobs/{cronJob}/toggle', [CronJobController::class, 'toggle'])->name('cronjobs.toggle');
    Route::delete('/cron-jobs/{cronJob}', [CronJobController::class, 'destroy'])->name('cronjobs.destroy');

    // File Manager
    Route::get('/file-manager', [FileManagerController::class, 'index'])->name('filemanager.index');
    Route::get('/file-manager/edit', [FileManagerController::class, 'edit'])->name('filemanager.edit');
    Route::post('/file-manager/write', [FileManagerController::class, 'write'])->name('filemanager.write');
    Route::post('/file-manager/upload', [FileManagerController::class, 'upload'])->name('filemanager.upload');
    Route::post('/file-manager/delete', [FileManagerController::class, 'delete'])->name('filemanager.delete');
    Route::post('/file-manager/rename', [FileManagerController::class, 'rename'])->name('filemanager.rename');
    Route::post('/file-manager/mkdir', [FileManagerController::class, 'mkdir'])->name('filemanager.mkdir');
    Route::post('/file-manager/chmod', [FileManagerController::class, 'chmod'])->name('filemanager.chmod');
    Route::post('/file-manager/archive', [FileManagerController::class, 'archive'])->name('filemanager.archive');
});
