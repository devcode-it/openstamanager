<?php

use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

// Route di fallback generale
Route::any('/legacy/{path}', [LegacyController::class, 'index'])
    ->name('legacy')
    ->where('path', '.*');
