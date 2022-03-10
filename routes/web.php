<?php

/** @noinspection UnusedFunctionResultInspection */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SetupController;
use App\Http\Middleware\CheckConfigurationMiddleware;
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

Route::get('/', static fn () => redirect()->route('auth.login'))
    ->middleware(CheckConfigurationMiddleware::class);

Route::name('auth.')
    ->middleware('guest')
    ->group(static function () {
        Route::inertia('login', 'LoginPage', ['external' => true])
            ->name('login');

        Route::post('login', [AuthController::class, 'login'])
            ->name('authenticate');

        Route::post('logout', [AuthController::class, 'logout'])
            ->withoutMiddleware('guest')
            ->middleware('auth')
            ->name('logout');
    });

Route::name('password.')
    ->middleware('guest')
    ->group(static function () {
        Route::post('forgot', [PasswordController::class, 'forgotPassword'])
            ->name('forgot');

        Route::inertia('reset', 'ResetPasswordPage', ['external' => true])
            ->name('reset');

        Route::post('reset', [PasswordController::class, 'resetPassword'])
            ->name('resetPassword');
    });

Route::name('setup.')->middleware(CheckConfigurationMiddleware::class)->group(static function () {
    Route::inertia('setup', 'SetupPage', [
        'languages' => cache()->rememberForever('app.languages', fn() => array_map(
            static fn($file) => basename($file, '.json'),
            glob(resource_path('lang') . '/*.json', GLOB_NOSORT)
        )),
        'license' => cache()->rememberForever('app.license', fn() => file_get_contents(base_path('LICENSE'))),
        'external' => true,
    ])
        ->name('index');

    Route::inertia('setup/admin', 'AdminSetupPage', ['external' => true])
        ->name('admin');

    Route::options('setup/test', [SetupController::class, 'testDatabase'])
        ->name('test');

    Route::put('setup/save', [SetupController::class, 'save'])
        ->name('save');

    Route::put('setup/admin', [SetupController::class, 'saveAdmin'])
        ->name('admin.save');
});

Route::get('lang/{language}', static function ($language) {
    app()->setLocale($language);

    return redirect()->back();
})->name('app.language');

Route::inertia('dashboard', 'Dashboard')
    ->middleware('auth')
    ->name('dashboard');
