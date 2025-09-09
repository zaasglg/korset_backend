<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Web\ProductShareController;

Route::get('/', function () {
    return view('welcome');
});

// Product share routes
Route::get('/share/product/{identifier?}', [ProductShareController::class, 'show'])->name('share.product');
Route::get('/share', [ProductShareController::class, 'show'])->name('share.product.query');
Route::post('/share/product/{product}/increment', [ProductShareController::class, 'incrementShare'])->name('share.increment');
