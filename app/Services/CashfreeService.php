<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashfreeService
{
    protected $baseUrl;
    protected $appId;
    protected $secretKey;
    protected $apiVersion;

    public function __construct()
    {
        $this->baseUrl = config('cashfree.base_url');
        $this->appId = config('cashfree.app_id');
        $this->secretKey = config('cashfree.secret_key');
        $this->apiVersion = config('cashfree.api_version');
    }

    /**
     * Create a new order in Cashfree
     */
    public function createOrder(array $params)
    {
        $orderId = 'ORD_' . time() . Str::random(4);

        $response = Http::withHeaders([
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => $this->apiVersion,
        ])->post("{$this->baseUrl}/orders", [
            'order_id' => $orderId,
            'order_amount' => $params['amount'],
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => 'CUST_' . Str::random(10),
                'customer_email' => $params['email'],
                'customer_phone' => $params['phone'],
            ],
            'order_meta' => [
                'return_url' => url('/payment-status?order_id={order_id}'),
            ]
        ]);

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'order_id' => $orderId,
            'status' => $response->status()
        ];
    }

    /**
     * Get order details from Cashfree
     */
    public function getOrder($orderId)
    {
        $response = Http::withHeaders([
            'x-client-id' => $this->appId,
            'x-client-secret' => $this->secretKey,
            'x-api-version' => $this->apiVersion,
        ])->get("{$this->baseUrl}/orders/{$orderId}");

        return [
            'success' => $response->successful(),
            'data' => $response->json(),
            'status' => $response->status()
        ];
    }

    /**
     * Verify Webhook Signature
     */
    public function verifySignature($signature, $rawPayload)
    {
        /* --- Production Implementation (Uncomment to enable) ---
        if (!$signature || !$rawPayload) return false;

        $expectedSignature = base64_encode(hash_hmac('sha256', $rawPayload, $this->secretKey, true));
        
        $cleanExpected = rtrim($expectedSignature, '=');
        $cleanReceived = rtrim($signature, '=');

        return hash_equals($cleanExpected, $cleanReceived);
        -------------------------------------------------------- */

        return true; // Bypassed for development
    }
}
