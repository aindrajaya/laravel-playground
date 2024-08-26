<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrowserController;

Route::get('/', [BrowserController::class, 'index']);
Route::get('/list', [BrowserController::class, 'list']);
Route::post('/simulate-browser', [BrowserController::class, 'simulateBrowser'])->name('simulate.browser');
