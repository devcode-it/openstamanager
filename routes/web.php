<?php

use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\HookController;
use App\Http\Controllers\InfoController;
use App\Http\Controllers\InitializationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\RequirementsController;
use App\Http\Controllers\Test;
use App\Http\Controllers\UpdateController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WidgetModalController;
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

    return redirect('legacy/controller.php?id_module='.$module->id);
})
    ->middleware(['auth']);

// Schermata dei requisiti
Route::get('/requirements', [RequirementsController::class, 'index'])
    ->name('requirements');

// Sezione di configurazione
Route::prefix('config')
    ->group(function () {
        Route::get('/', [ConfigurationController::class, 'index'])
            ->name('configuration');

        Route::get('/test', [ConfigurationController::class, 'test'])
            ->name('configuration-test');

        Route::get('/cache', [ConfigurationController::class, 'cache'])
            ->name('configuration-cache');

        Route::get('/write', [ConfigurationController::class, 'write'])
            ->name('configuration-write');

        Route::post('/save', [ConfigurationController::class, 'save'])
            ->name('configuration-save');
    });

// Installazione aggiornamenti del gestionale
Route::get('/update', [UpdateController::class, 'index'])
    ->name('update');
Route::post('/update', [UpdateController::class, 'execute'])
    ->name('update-execute');

// Inizializzazione del gestionale
Route::get('/init', [InitializationController::class, 'index'])
    ->name('initialization');
Route::post('/init', [InitializationController::class, 'save'])
    ->name('initialization-save');

// Messaggi flash
Route::get('/messages', [MessageController::class, 'index'])
    ->name('messages');

// Operazioni Ajax
Route::prefix('ajax')
    ->group(function () {
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
Route::prefix('hook')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/list', [HookController::class, 'list'])
            ->name('hooks');

        Route::get('/lock/{hook_id}', [HookController::class, 'lock'])
            ->whereNumber('hook_id')
            ->name('hook-lock');

        Route::get('/execute/{hook_id}/{token}', [HookController::class, 'execute'])
            ->whereNumber('hook_id')
            ->name('hook-execute');

        Route::get('/response/{hook_id}', [HookController::class, 'response'])
            ->whereNumber('hook_id')
            ->name('hook-response');
    });

// Informazioni su OpenSTAManager
Route::get('/info', [InfoController::class, 'info'])
    ->middleware(['auth'])
    ->name('info');

// Segnalazione bug
Route::prefix('bug')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('', [InfoController::class, 'bug'])
            ->name('bug');

        Route::post('', [InfoController::class, 'send'])
            ->name('bug-send');
    });

// Log di accesso
Route::get('/logs', [UserController::class, 'logs'])
    ->middleware(['auth'])
    ->name('logs');

// Log di accesso
Route::get('/widget/modal/{id}', [WidgetModalController::class, 'modal'])
    ->whereNumber('id')
    ->middleware(['auth'])
    ->name('widget-modal');

// Informazioni sull'utente
Route::prefix('user')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/info', [UserController::class, 'index'])
            ->name('user-info');

        Route::get('/password', [UserController::class, 'password'])
            ->name('user-password');

        Route::post('/password', [UserController::class, 'savePassword'])
            ->name('user-password-save');

        Route::get('/photo', [UserController::class, 'photo'])
            ->name('user-photo');

        Route::post('/photo', [UserController::class, 'savePhoto'])
            ->name('user-photo-save');
    });
