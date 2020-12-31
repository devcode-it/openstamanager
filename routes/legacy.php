<?php

use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

Route::any('/', [LegacyController::class, 'index']);

Route::any('/{path}', [LegacyController::class, 'index'])
    ->name('legacy')
    ->where('path', '.*');
