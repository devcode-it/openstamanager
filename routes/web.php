<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
|
*/

// Pagina principale
$app->get('/', 'Controllers\BaseController:index')->setName('login');
$app->post('/', 'Controllers\BaseController:loginAction')
    ->add('Middlewares\Authorization\GuestMiddleware');
$app->get('/logout[/]', 'Controllers\BaseController:logout')->setName('logout')
    ->add('Middlewares\Authorization\UserMiddleware');

// Configurazione iniziale
$app->group('', function () use ($app) {
    $app->get('/requirements[/]', 'Controllers\Config\RequirementsController:requirements')->setName('requirements');

    $app->get('/configuration[/]', 'Controllers\Config\ConfigurationController:configuration')->setName('configuration');
    $app->post('/configuration[/]', 'Controllers\Config\ConfigurationController:configurationSave');

    $app->post('/configuration/test[/]', 'Controllers\Config\ConfigurationController:configurationTest')->setName('configurationTest');

    $app->get('/init[/]', 'Controllers\Config\InitController:init')->setName('init');
    $app->post('/init[/]', 'Controllers\Config\InitController:initSave');

    $app->get('/update[/]', 'Controllers\Config\UpdateController:update')->setName('update');
    $app->get('/update/progress[/]', 'Controllers\Config\UpdateController:updateProgress')->setName('update-progress');
})->add('Middlewares\Authorization\GuestMiddleware');

// Informazioni su OpenSTAManager
$app->get('/info[/]', 'Controllers\ConfigController:info')->setName('info')
    ->add('Middlewares\Authorization\UserMiddleware');

// Operazioni Ajax
$app->group('/ajax', function () use ($app) {
    $app->get('/select[/]', 'Controllers\AjaxController:select')->setName('ajax-select');
    $app->get('/complete[/]', 'Controllers\AjaxController:complete')->setName('ajax-complete');
    $app->get('/search[/]', 'Controllers\AjaxController:search')->setName('ajax-search');

    $app->get('/dataload[/]', 'Controllers\AjaxController:dataLoad')->setName('ajax-dataload');

    $app->get('/session[/]', 'Controllers\AjaxController:session')->setName('ajax-session');
    $app->get('/session-array[/]', 'Controllers\AjaxController:search')->setName('ajax-session-array');
})->add('Middlewares\Authorization\UserMiddleware');

// Moduli
$app->group('/module/{module_id}', function () use ($app) {
    $app->get('[/]', 'Controllers\ModuleController:index')->setName('module');

    $app->get('/edit/{record_id}[/]', 'Controllers\ModuleController:record')->setName('module-record');
    $app->post('/edit/{record_id}', 'Controllers\ModuleController:saveRecord');

    $app->get('/add[/]', 'Controllers\ModuleController:add')->setName('module-add');
    $app->post('/add[/]', 'Controllers\ModuleController:addRecord');
    $app->post('[/]', 'Controllers\ModuleController:addRecord');
})->add('Middlewares\Authorization\UserMiddleware');

// Stampe
$app->get('/print/{print_id}[/]', 'PrintController:index')->setName('print')
    ->add('Middlewares\Authorization\UserMiddleware');

// Moduli
$app->group('/upload', function () use ($app) {
    $app->get('/{upload_id}[/]', 'Controllers\UploadController:view')->setName('upload-view');

    $app->get('/add/{module_id}/{plugin_id}/{record_id}[/]', 'Controllers\UploadController:index')->setName('upload');

    $app->get('/remove/{upload_id}[/]', 'Controllers\UploadController:remove')->setName('upload-remove');
})->add('Middlewares\Authorization\UserMiddleware');

// E-mail
$app->get('/mail/{mail_id}[/]', 'MailController:index')->setName('mail')
    ->add('Middlewares\Authorization\UserMiddleware');

/*
Route::get('/', 'BaseController:index');

// Authentication routes
//Auth::routes();
Route::get('login', 'Auth\LoginController:showLoginForm')->setName('login');
$app->post('login', 'Auth\LoginController:login');
$app->post('logout', 'Auth\LoginController:logout')->setName('logout');

// Base routes
Route::get('/info', 'InfoController:index')->setName('info');
Route::get('/bug', 'InfoController:bug')->setName('bug');
Route::get('/user', 'InfoController:user')->setName('user');
Route::get('/user-logs', 'InfoController:logs')->setName('logs');

// Modules
Route::prefix('module/{module}')->group(function () {
   $app->get('', 'ModuleController:index')->setName('module');

   $app->get('/{record_id}', 'ModuleController:record')->setName('module-record');
    $app->post('/{record_id}', 'ModuleController:saveRecord');

   $app->get('/add', 'ModuleController:add')->setName('module-add');
    $app->post('/add', 'ModuleController:addRecord');
});

// Plugins
Route::prefix('plugin')->group(function () {
   $app->get('/{plugin}', 'PluginController:index')->setName('plugin');

   $app->get('/{plugin}/{record_id}', 'PluginController:record')->setName('plugin-record');
    $app->post('/{plugin}/{record_id}', 'PluginController:saveRecord');

   $app->get('/add/{parent_id}/{plugin}', 'PluginController:record')->setName('plugin-add');
    $app->post('/add/{parent_id}/{plugin}', 'PluginController:addRecord');
});

// Prints
Route::get('/print/{print_id}', 'PrintController:index')->setName('print');

// Mails
Route::get('/mail/{mail_id}', 'MailController:index')->setName('mail');
*/
