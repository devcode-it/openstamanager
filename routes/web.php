<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/login-redirect', function (Request $request) {
    $url = $request->url();
    if (stripos($url, '/public/') !== false) {
        return redirect(substr($url, 0, stripos($url, 'public')));
    }
})->name('login');