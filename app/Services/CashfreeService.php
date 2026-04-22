<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CashfreeService
{
    protected $clientId;
    protected $clientSecret;
    protected $baseUrl;
    protected $apiVersion;

    public function __construct()
    {
        $this->clientId = config('services.cashfree.client_id');
        $this->clientSecret = config('services.cashfree.client_secret');
        $this->apiVersion = '2025-01-01';
        
        $env = config('services.cashfree.env', 'sandbox');
        $this->baseUrl = $env === 'production' 
            ? 'https://api.cashfree.com/pg' 
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Create an order in Cashfree
     */
    public function createOrder($orderId, $amount, $email, $phone)
    {   
        $response = Http::withHeaders([
            'x-client-id' => $this->clientId,
            'x-client-secret' => $this->clientSecret,
            'x-api-version' => $this->apiVersion,
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}/orders", [
            'order_id' => $orderId,
            'order_amount' => (float) $amount,
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id' => "cust_" . str_replace('order_', '', $orderId),
                'customer_email' => $email,
                'customer_phone' => $phone,
            ],
            'order_meta' => [
                'return_url' => url('/api/payments/verify/' . $orderId),
                'notify_url' => url('/api/payments/webhook'),
            ]
        ]);

        if ($response->failed()) {
            Log::error('Cashfree API Error:', [
                'status' => $response->status(),
                'body' => $response->json(),
            ]);
            throw new \Exception('Cashfree API Error: ' . $response->body());
        }

        return $response->object();
    }

    /**
     * Get order details from Cashfree
     */
    public function getOrder($orderId)
    {
        $response = Http::withHeaders([
            'x-client-id' => $this->clientId,
            'x-client-secret' => $this->clientSecret,
            'x-api-version' => $this->apiVersion,
        ])->get("{$this->baseUrl}/orders/{$orderId}");

        return $response->object();
    }

    /**
     * Verify Cashfree Webhook Signature
     */
    public function verifySignature($signature, $timestamp, $rawData)
    {
        $data = $timestamp . $rawData;
        $computedSignature = base64_encode(hash_hmac('sha256', $data, $this->clientSecret, true));
        return $signature === $computedSignature;
    }
}
