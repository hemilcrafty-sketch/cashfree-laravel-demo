<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

use App\Http\Controllers\PaymentController;

Route::prefix('payment')->group(function () {
    Route::post('/create', [PaymentController::class, 'createOrder']);
    Route::post('/webhook', [PaymentController::class, 'handleWebhook']);
    Route::get('/success', [PaymentController::class, 'successCallback']);
});
