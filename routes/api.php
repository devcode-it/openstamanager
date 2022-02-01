<?php

/** @noinspection UnusedFunctionResultInspection */

use LaravelJsonApi\Laravel\Facades\JsonApiRoute;
use LaravelJsonApi\Laravel\Http\Controllers\JsonApiController;

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
    ->resources(function ($server) {
        $server->resource('users', JsonApiController::class);
    });

/*
 * Prossimamente, per le chiamate alla API autenticate:
 *
 * Route::middleware('auth:api')->group(function () {

});
*/
