<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

Route::get('/', function () {
    return view('welcome');
})->name('payment.form');

Route::post('/payment/process', [PaymentController::class, 'redirectToCheckout'])->name('payment.process');

Route::get('/checkout', function () {
    return view('payment.checkout');
})->name('payment.checkout');

Route::get('/payment', [PaymentController::class, 'showPaymentForm'])->name('payment.form');
