<?php

use App\Http\Controllers\Api\WhmcsApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

/*
|--------------------------------------------------------------------------
| Opterius Provisioning API v1
|--------------------------------------------------------------------------
|
| Used by WHMCS, Opterius Hub, and other billing systems to auto-create,
| suspend, unsuspend, and terminate hosting accounts.
|
*/

Route::prefix('v1')->middleware('api_key')->group(function () {

    // Connection test
    Route::post('/test', [WhmcsApiController::class, 'testConnection']);

    // Account provisioning
    Route::post('/accounts/create', [WhmcsApiController::class, 'createAccount']);
    Route::post('/accounts/suspend', [WhmcsApiController::class, 'suspendAccount']);
    Route::post('/accounts/unsuspend', [WhmcsApiController::class, 'unsuspendAccount']);
    Route::post('/accounts/terminate', [WhmcsApiController::class, 'terminateAccount']);
    Route::post('/accounts/password', [WhmcsApiController::class, 'changePassword']);
    Route::post('/accounts/package', [WhmcsApiController::class, 'changePackage']);

    // Info
    Route::get('/packages', [WhmcsApiController::class, 'listPackages']);
    Route::post('/accounts/usage', [WhmcsApiController::class, 'getUsage']);
});
