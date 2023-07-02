<?php

use App\Http\Controllers\Controller;
use App\Http\Middleware\CheckConfigurationMiddleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\RedirectIfAuthenticated;
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

Route::get('/', static fn () => redirect()->route('login'))
    ->middleware(CheckConfigurationMiddleware::class)
    /** @psalm-suppress PossiblyInvalidMethodCall */
    ->withoutMiddleware([LocaleMiddleware::class, RedirectIfAuthenticated::class]);

Route::middleware('guest')->group(static function () {
    Route::inertia('login', 'LoginPage', ['external' => true])
        ->middleware('guest')
        ->name('login');

    Route::inertia('reset', 'ResetPasswordPage', ['external' => true])
        ->middleware('guest')
        ->name('password.reset');

    Route::inertia('setup', 'Setup/SetupPage', [
        'languages' => app(Controller::class)->getLanguages(),
        'license' => cache()->rememberForever('app.license', static fn () => file_get_contents(base_path('LICENSE'))),
        'external' => true,
    ])
        ->middleware(CheckConfigurationMiddleware::class)
        ->withoutMiddleware([LocaleMiddleware::class, RedirectIfAuthenticated::class])
        ->name('setup.index');
});

Route::patch('lang', [Controller::class, 'setLanguage'])->name('app.language');

Route::middleware('auth')->group(static function () {
    Route::inertia('dashboard', 'Dashboard')
        ->middleware('auth')
        ->name('dashboard');

    Route::inertia('users', 'Users/UsersRecords')
        ->middleware('auth')
        ->name('users.index');

    Route::inertia('users/{id}', 'Users/UserRecord')
        ->middleware('auth')
        ->name('users.show');
});

Route::get('refresh_csrf', static fn () => static function () {
    session()->regenerate();

    return response()->json(['token' => csrf_token()]);
})
    ->name('csrf.renew');
