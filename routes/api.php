<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

/*
| API Routes for Cashfree Integration
*/

Route::post('/cashfree/webhook', [PaymentController::class, 'handleWebhook']);

// You can keep the others for debugging if you want, but the above is the main one requested.
Route::prefix('payments')->group(function () {
    Route::get('/verify/{order_id}', [PaymentController::class, 'verifyPayment']); // Fixed version might be needed later
});



