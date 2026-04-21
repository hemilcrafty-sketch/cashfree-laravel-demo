<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PaymentController;

/*
|--------------------------------------------------------------------------
| API Routes for Cashfree REST Integration
|--------------------------------------------------------------------------
*/

// Create Order API
Route::post('/create-order', [PaymentController::class, 'createOrder']);

// Check Payment Status API
Route::get('/payment-status/{order_id}', [PaymentController::class, 'paymentStatus']);

// Webhook Handler API
Route::post('/cashfree/webhook', [PaymentController::class, 'handleWebhook']);
