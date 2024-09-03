<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BrowserController;

Route::get('/', [BrowserController::class, 'index']);
Route::get('/list', [BrowserController::class, 'list']);
Route::post('/simulate-browser', [BrowserController::class, 'simulateBrowser'])->name('simulate.browser');
Route::post('/browser-simulator/fetch-html-guzzle', [BrowserController::class, 'fetchHtmlWithGuzzle'])->name('browser.simulator.fetch.guzzle');
Route::post('/browser-simulator/fetch-html-file', [BrowserController::class, 'fetchHtmlWithFileGetContents'])->name('browser.simulator.fetch.file');
Route::post('/browser-simulator/fetch-html', [BrowserController::class, 'fetchHtml'])->name('browser.simulator.fetch.html');
