<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
| API Routes for Cashfree Integration
*/

Route::prefix('payments')->group(function () {
    Route::post('/create-order', [PaymentController::class, 'createOrder']);
    Route::get('/verify/{order_id}', [PaymentController::class, 'verifyPayment']);
    Route::get('/{order_id}', [PaymentController::class, 'showPayment']); // Added for completeness
    Route::post('/webhook', [PaymentController::class, 'handleWebhook']);
    Route::post('/test-signature', [PaymentController::class, 'generateTestSignature']); // Helper for testing
    Route::post('/simulate-webhook', [PaymentController::class, 'simulateWebhook']); // FULL Simulate
});



