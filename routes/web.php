<?php

/** @noinspection UnusedFunctionResultInspection */

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

Route::get('/', function () {
    if (empty(DB::connection()->getDatabaseName())) {
        return route('setup');
    }

    //return route('auth.login');
});

// ----- PUBLIC ROUTES ----- //
Route::inertia('setup', 'SetupPage', [
    'languages' => cache()->rememberForever('app.languages', fn () => array_map(
        static fn ($file) => basename($file, '.json'),
        glob(resource_path('lang').'/*.json', GLOB_NOSORT)
    )),
    'license' => cache()->rememberForever('app.license', fn () => file_get_contents(base_path('LICENSE'))),
]);
Route::options('setup/test', [SetupController::class, 'testDatabase'])->name('setup.test')->withoutMiddleware('csrf');
Route::put('setup/save', [SetupController::class, 'save'])->name('setup.save');

Route::get('lang/{language}', function ($language) {
    app()->setLocale($language);

    return redirect()->back();
})->name('app.language');