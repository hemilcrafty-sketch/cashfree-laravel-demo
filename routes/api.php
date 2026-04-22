<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes for Cashfree REST Integration
|--------------------------------------------------------------------------
*/

Route::prefix('payments')->group(function () {
    Route::post('/create-order', [PaymentController::class, 'createOrder'])->name('payment.create');
    Route::get('/verify/{order_id}', [PaymentController::class, 'verifyPayment'])->name('payment.verify');
    Route::post('/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
});
