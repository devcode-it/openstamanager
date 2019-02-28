<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

$app->get('/', function ($request, $response, $args) {
    return $response->write('Hello World!');
});

$app->get('/info', 'Controllers\BaseController:info')->setName('info');

/*
Route::get('/', 'BaseController@index');

// Authentication routes
//Auth::routes();
Route::get('login', 'Auth\LoginController@showLoginForm')->name('login');
Route::post('login', 'Auth\LoginController@login');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

// Base routes
Route::get('/info', 'InfoController@index')->name('info');
Route::get('/bug', 'InfoController@bug')->name('bug');
Route::get('/user', 'InfoController@user')->name('user');
Route::get('/user-logs', 'InfoController@logs')->name('logs');

// Modules
Route::prefix('module/{module}')->group(function () {
    Route::get('', 'ModuleController@index')->name('module');

    Route::get('/{record_id}', 'ModuleController@record')->name('module-record');
    Route::post('/{record_id}', 'ModuleController@saveRecord');

    Route::get('/add', 'ModuleController@add')->name('module-add');
    Route::post('/add', 'ModuleController@addRecord');
});

// Plugins
Route::prefix('plugin')->group(function () {
    Route::get('/{plugin}', 'PluginController@index')->name('plugin');

    Route::get('/{plugin}/{record_id}', 'PluginController@record')->name('plugin-record');
    Route::post('/{plugin}/{record_id}', 'PluginController@saveRecord');

    Route::get('/add/{parent_id}/{plugin}', 'PluginController@record')->name('plugin-add');
    Route::post('/add/{parent_id}/{plugin}', 'PluginController@addRecord');
});

// Prints
Route::get('/print/{print_id}', 'PrintController@index')->name('print');

// Mails
Route::get('/mail/{mail_id}', 'MailController@index')->name('mail');
*/
