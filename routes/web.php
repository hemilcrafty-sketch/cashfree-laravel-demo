<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('payment.form');

Route::post('/payment/process', [App\Http\Controllers\Api\PaymentController::class, 'redirectToCheckout'])->name('payment.process');
