<?php

use App\Http\Controllers\Api\SetupController;
use App\Http\Middleware\CheckConfigurationMiddleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group, which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::name('setup.')
    ->withoutMiddleware([EnsureFrontendRequestsAreStateful::class, CheckConfigurationMiddleware::class]) // TODO: Add configuration check middleware when setup is done
    ->group(static function () {
        Route::post('setup/test', [SetupController::class, 'testDatabase'])
            ->name('test');

        Route::put('setup/save', [SetupController::class, 'save'])
            ->name('save');
    });

Route::restifyAuth();
