<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use DevCode\Aggiornamenti\Controllers\AggiornamentiController;
use DevCode\Aggiornamenti\Controllers\ControlliAggiuntiviController;

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

Route::prefix('aggiornamenti')
    ->group(function () {
        Route::get('', [AggiornamentiController::class, 'index']);

        Route::post('controllo-versione', [AggiornamentiController::class, 'check'])
            ->name('controllo-versione');

        Route::post('scarica-aggiornamento', [AggiornamentiController::class, 'download'])
            ->name('scarica-aggiornamento');

        Route::get('checksum', [ControlliAggiuntiviController::class, 'checksum'])
            ->name('checksum');
        Route::get('database', [ControlliAggiuntiviController::class, 'database'])
            ->name('database');
        Route::get('controlli', [ControlliAggiuntiviController::class, 'controlli'])
            ->name('controlli');
    });
