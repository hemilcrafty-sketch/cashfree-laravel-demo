<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * Create a Cashfree Order
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'currency' => 'required|string|size:3',
            'customer_id' => 'required|string',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|string|min:10',
        ]);

        $orderId = 'ORDER_' . Str::upper(Str::random(10));

        // 1. Save to local database
        $payment = Payment::create([
            'order_id' => $orderId,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'status' => 'pending',
        ]);

        // 2. Prepare Cashfree API Request
        $config = config('services.cashfree');
        
        $response = Http::withHeaders([
            'x-api-version' => $config['version'],
            'x-client-id' => $config['app_id'],
            'x-client-secret' => $config['secret_key'],
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->post($config['base_url'] . '/orders', [
            'order_id' => $orderId,
            'order_amount' => (float) $request->amount,
            'order_currency' => $request->currency,
            'customer_details' => [
                'customer_id' => $request->customer_id,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
            ],
            'order_meta' => [
                'return_url' => url('/api/payment/success?order_id={order_id}'),
                'notify_url' => url('/api/payment/webhook'),
            ],
        ]);

        if ($response->failed()) {
            $payment->update([
                'status' => 'failed',
                'raw_response' => $response->json(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order with Cashfree',
                'error' => $response->json(),
            ], 400);
        }

        $responseData = $response->json();
        
        return response()->json([
            'success' => true,
            'order_id' => $orderId,
            'payment_session_id' => $responseData['payment_session_id'] ?? null,
            'data' => $responseData,
        ]);
    }

    /**
     * Handle Cashfree Webhook
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->all();
        Log::info('Cashfree Webhook Received:', $payload);

        // Simple validation: Ensure order_id exists
        $orderId = $payload['data']['order']['order_id'] ?? null;
        
        if (!$orderId) {
            return response()->json(['message' => 'Invalid payload'], 400);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        // Update payment based on status
        $status = $payload['data']['payment']['payment_status'] ?? 'PENDING';
        
        $paymentStatus = match(Str::upper($status)) {
            'SUCCESS' => 'success',
            'FAILED' => 'failed',
            'CANCELLED' => 'failed',
            default => 'pending',
        };

        $payment->update([
            'status' => $paymentStatus,
            'payment_id' => $payload['data']['payment']['cf_payment_id'] ?? null,
            'raw_response' => $payload,
        ]);

        return response()->json(['message' => 'Webhook processed']);
    }

    /**
     * Payment Success Callback (Redirect)
     */
    public function successCallback(Request $request)
    {
        $orderId = $request->query('order_id');
        
        if (!$orderId) {
            return response()->json(['message' => 'Missing order ID'], 400);
        }

        $payment = Payment::where('order_id', $orderId)->first();

        return response()->json([
            'success' => true,
            'message' => 'Payment status for ' . $orderId,
            'data' => $payment
        ]);
    }
}
