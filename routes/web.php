<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\InfoController;
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

// Percorsi di autenticazione e gestione utenza
require __DIR__.'/auth.php';

// Sezione di configurazione
Route::get('/config', [ConfigurationController::class, 'index'])
    ->name('configuration');
Route::get('/config-test', [ConfigurationController::class, 'test'])
    ->name('configuration-test');
Route::post('/config', [ConfigurationController::class, 'save'])
    ->name('configuration-save');

// Messaggi flash
Route::get('/messages', [Test::class, 'index'])
    ->name('messages');

// Operazioni Ajax
Route::prefix('ajax')->group(function () {
    Route::get('/select', [Test::class, 'index'])
        ->name('ajax-select');
    Route::get('/complete', [Test::class, 'index'])
        ->name('ajax-complete');
    Route::get('/search', [Test::class, 'index'])
        ->name('ajax-search');

    // Sessioni
    Route::get('/session/', [Test::class, 'index'])
        ->name('ajax-session');
    Route::get('/session-array/', [Test::class, 'index'])
        ->name('ajax-session-array');

    // Dataload
    Route::get('/dataload/{module_id}/{reference_id?}', [Test::class, 'index'])
        ->where('module_id', '[0-9]+')
        ->where('reference_id', '[0-9]+')
        ->name('ajax-dataload');
});

// Hooks
Route::prefix('hook')->group(function () {
    Route::get('/list', [Test::class, 'index'])
        ->name('hooks');

    Route::get('/lock/{hook_id}', [Test::class, 'index'])
        ->where('hook_id', '[0-9]+')
        ->name('hook-lock');

    Route::get('/execute/{hook_id}/{token}', [Test::class, 'index'])
        ->where('hook_id', '[0-9]+')
        ->name('hook-execute');

    Route::get('/response/{hook_id}', [Test::class, 'index'])
        ->where('hook_id', '[0-9]+')
        ->name('hook-response');
});

// Informazioni su OpenSTAManager
Route::get('/info', [InfoController::class, 'info'])
    ->name('info');

// Segnalazione bug
Route::get('/bug', [Test::class, 'index'])
    ->name('bug');
Route::post('/bug', [Test::class, 'index']);

// Log di accesso
Route::get('/logs', [Test::class, 'index'])
    ->name('logs');

// Informazioni sull'utente
Route::get('/user', [Test::class, 'index'])
    ->name('user');

Route::get('/password', [Test::class, 'index'])
    ->name('user-password');
Route::post('/password', [Test::class, 'index']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');
