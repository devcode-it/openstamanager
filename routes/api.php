<?php

/** @noinspection UnusedFunctionResultInspection */

use App\Http\Controllers\Controller;
use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;
use LaravelJsonApi\Laravel\Routing\ResourceRegistrar;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

JsonApiRoute::server('v1')
    ->prefix('v1')
    ->resources(function (ResourceRegistrar $server) {
        $server->resource('users', JsonApiController::class);
    });

Route::get('modules', [Controller::class, 'getModules'])
    ->name('modules');

Route::get('refresh_csrf', static fn() => response()->json(csrf_token()))
    ->middleware('web')
    ->name('csrf.renew');
