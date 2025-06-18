<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReferralController;

Route::get('/', function () {
    return view('welcome');
});
