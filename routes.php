<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use DevCode\CausaliTrasporto\Controllers\CausaliTrasportoController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('web')
    ->group(function () {
        // Route di fallback generale
        Route::any('/legacy/{path}', [LegacyController::class, 'index'])
            ->name('legacy')
            ->where('path', '.*');
    });
