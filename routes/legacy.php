<?php

use App\Http\Controllers\LegacyController;
use Illuminate\Support\Facades\Route;

// Route di fallback generale
Route::fallback([LegacyController::class, 'index']);
