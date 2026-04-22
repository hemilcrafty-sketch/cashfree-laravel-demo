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
    Route::get('/{order_id}', [PaymentController::class, 'paymentStatus']);
    Route::get('/verify/{order_id}', [PaymentController::class, 'verifyPayment']);
    Route::post('/webhook', [PaymentController::class, 'webhook']);
});
