<?php

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

// ----- INERTIA ROUTES ----- //
Route::inertia('setup', 'SetupPage');

Route::get('lang/{language}', function ($language) {
    app()->setLocale($language);

    return redirect()->back();
})->name('language');
