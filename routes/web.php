<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/payment-status', [App\Http\Controllers\PaymentController::class, 'paymentStatus']);

