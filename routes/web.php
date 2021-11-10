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

Route::redirect('/', 'setup');

// ----- PUBLIC ROUTES ----- //
Route::inertia('setup', 'SetupPage', [
    'languages' => cache()->rememberForever('app.languages', fn () => array_map(
        static fn ($file) => basename($file, '.json'),
        glob(resource_path('lang').'/*.json', GLOB_NOSORT)
    )),
    'license' => cache()->rememberForever('app.license', fn () => file_get_contents(base_path('LICENSE'))),
]);
Route::options('setup/test', [SetupController::class, 'testDatabase'])->name('setup.test')->withoutMiddleware('csrf')->middleware(\Illuminatech\MultipartMiddleware\MultipartFormDataParser::class);

Route::get('lang/{language}', function ($language) {
    app()->setLocale($language);

    return redirect()->back();
})->name('language');
