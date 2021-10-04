<?php

/** @noinspection UnusedFunctionResultInspection */

use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

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

Route::apiResource('users', UserController::class);

/*
 * Prossimamente, per le chiamate alla API autenticate:
 *
 * Route::middleware('auth:api')->group(function () {

});
*/
