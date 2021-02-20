<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\InitializationController;
use App\Http\Controllers\Test;
use App\Http\Controllers\UserController;
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

// Redirect predefinito a seguito del login
Route::get('/', function () {
    $module = auth()->user()->getFirstAvailableModule();

    return redirect('controller.php?id_module='.$module->id);
})
    ->middleware('auth');

// Sezione di configurazione
Route::get('/config', [ConfigurationController::class, 'index'])
    ->name('configuration');
Route::get('/config-test', [ConfigurationController::class, 'test'])
    ->name('configuration-test');
Route::post('/config', [ConfigurationController::class, 'save'])
    ->name('configuration-save');

// Inizializzazione del gestionale
Route::get('/init', [InitializationController::class, 'index'])
    ->name('initialization');
Route::post('/init', [InitializationController::class, 'save'])
    ->name('initialization-save');

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
Route::get('/bug', [InfoController::class, 'bug'])
    ->name('bug');
Route::post('/bug', [InfoController::class, 'send'])
    ->name('bug-send');

// Log di accesso
Route::get('/logs', [UserController::class, 'logs'])
    ->name('logs');

// Informazioni sull'utente
Route::get('/user', [UserController::class, 'index'])
    ->name('user-info');

Route::get('/password', [UserController::class, 'password'])
    ->name('user-password');
Route::post('/password', [UserController::class, 'save'])
    ->name('user-password-save');;
