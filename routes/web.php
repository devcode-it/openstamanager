<?php

use App\Http\Controllers\LegacyController;
use App\Http\Controllers\Test;
use Illuminate\Support\Facades\Route;

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

Route::get('/test', function () {
    return view('welcome');
});

Route::get('/login', function () {
    return view('user.login');
});

// Messaggi flash
Route::get('/messages', [Test::class, 'index'])
    ->name('messages');

// Hooks
Route::prefix('hook')->group(function () {
    Route::get('/list',  [Test::class, 'index'])
        ->name('hooks');

    Route::get('/lock/{hook_id:[0-9]+}',  [Test::class, 'index'])
        ->name('hook-lock');

    Route::get('/execute/{hook_id:[0-9]+}/{token}',  [Test::class, 'index'])
        ->name('hook-execute');

    Route::get('/response/{hook_id:[0-9]+}',  [Test::class, 'index'])
        ->name('hook-response');
});

// Informazioni su OpenSTAManager
Route::get('/info',  [Test::class, 'index'])
    ->name('info');

// Segnalazione bug
Route::get('/bug',  [Test::class, 'index'])
    ->name('bug');
Route::post('/bug',  [Test::class, 'index']);

// Log di accesso
Route::get('/logs',  [Test::class, 'index'])
    ->name('logs');

// Informazioni sull'utente
Route::get('/user',  [Test::class, 'index'])
    ->name('user');

Route::get('/password',  [Test::class, 'index'])
    ->name('user-password');
Route::post('/password',  [Test::class, 'index']);
