<?php

/** @noinspection UnusedFunctionResultInspection */

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SetupController;
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

Route::get('/', static function () {
    if (empty(DB::connection()->getDatabaseName())) {
        return redirect()->route('setup');
    }

    return redirect()->route('auth.login');
});

Route::name('auth.')->group(static function () {
    Route::inertia('login', 'LoginPage')
        ->name('login');
    /*Route::inertia('password-request', '')
        ->name('password-request');*/

    Route::post('login', [AuthController::class, 'authenticate'])
        ->name('authenticate');
    /*Route::post('logout', 'Auth\LoginController@logout')
        ->name('auth.logout');*/
});

Route::name('setup.')->group(static function () {
    Route::inertia('setup', 'SetupPage', [
        'languages' => cache()->rememberForever('app.languages', fn () => array_map(
            static fn ($file) => basename($file, '.json'),
            glob(resource_path('lang').'/*.json', GLOB_NOSORT)
        )),
        'license' => cache()->rememberForever('app.license', fn () => file_get_contents(base_path('LICENSE'))),
    ]);

    Route::inertia('setup/admin', 'AdminSetupPage')
        ->name('admin');

    Route::options('setup/test', [SetupController::class, 'testDatabase'])
        ->name('test')
        ->withoutMiddleware('csrf');

    Route::put('setup/save', [SetupController::class, 'save'])
        ->name('save');

    Route::put('setup/admin', [SetupController::class, 'saveAdmin'])
        ->name('admin.save');
});



Route::get('lang/{language}', static function ($language) {
    app()->setLocale($language);

    return redirect()->back();
})->name('app.language');
