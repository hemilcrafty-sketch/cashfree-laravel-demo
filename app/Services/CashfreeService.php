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
        $this->clientId = trim(config('services.cashfree.client_id'));
        $this->clientSecret = trim(config('services.cashfree.client_secret'));
        $this->apiVersion = '2023-08-01';
        
        $env = trim(config('services.cashfree.env', 'sandbox'));
        $this->baseUrl = ($env === 'production') 
            ? 'https://api.cashfree.com/pg' 
            : 'https://sandbox.cashfree.com/pg';
    }

    /**
     * Create an order in Cashfree
     */
    public function createOrder($orderId, $amount, $email, $phone)
    {   
        try {
            $payload = [
                'order_id' => $orderId,
                'order_amount' => (float) $amount,
                'order_currency' => 'INR',
                'customer_details' => [
                    'customer_id' => $orderId,
                    'customer_name' => 'Test Customer',
                    'customer_email' => $email,
                    'customer_phone' => $phone,
                ],
                'order_meta' => [
                    'return_url' => request()->root() . '/api/payments/verify/' . $orderId,
                    'notify_url' => request()->root() . '/api/payments/webhook',
                ]
            ];

            Log::info('Cashfree API Request Payload:', $payload);

            $response = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-client-secret' => $this->clientSecret,
                'x-api-version' => $this->apiVersion,
                'Content-Type' => 'application/json',
            ])
            ->timeout(15)
            ->retry(3, 100)
            ->post("{$this->baseUrl}/orders", $payload);

            if ($response->failed()) {
                Log::error('Cashfree API Order Creation Failed', [
                    'status' => $response->status(),
                    'response' => $response->json(),
                ]);
                throw new \Exception('Payment gateway error. Please try again later.');
            }

            $obj = $response->object();
            Log::info('Cashfree RAW Response: ' . $response->body());
            return $obj;
        } catch (\Exception $e) {
            Log::critical('Cashfree Service Exception: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get order details from Cashfree
     */
    public function getOrder($orderId)
    {
        try {
            $response = Http::withHeaders([
                'x-client-id' => $this->clientId,
                'x-client-secret' => $this->clientSecret,
                'x-api-version' => $this->apiVersion,
            ])
            ->timeout(15)
            ->retry(3, 100)
            ->get("{$this->baseUrl}/orders/{$orderId}");

            if ($response->failed()) {
                Log::error('Cashfree API Get Order Failed', [
                    'order_id' => $orderId,
                    'status' => $response->status(),
                ]);
                return null;
            }

            return $response->object();
        } catch (\Exception $e) {
            Log::error('Cashfree GetOrder Exception: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Verify Cashfree Webhook Signature (Securely)
     */
    public function verifySignature($signature, $timestamp, $rawData)
    {
        $data = $timestamp . $rawData;
        $computedSignature = base64_encode(hash_hmac('sha256', $data, $this->clientSecret, true));
        
        // Use hash_equals to prevent timing attacks
        return hash_equals($signature, $computedSignature);
    }
}
