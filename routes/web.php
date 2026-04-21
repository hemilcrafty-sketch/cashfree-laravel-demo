<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/pay', [App\Http\Controllers\PaymentController::class, 'showPaymentForm'])->name('payment.form');
Route::post('/pay', [App\Http\Controllers\PaymentController::class, 'createOrder'])->name('payment.create');
Route::get('/payment-status', [App\Http\Controllers\PaymentController::class, 'paymentStatus'])->name('payment.status');

