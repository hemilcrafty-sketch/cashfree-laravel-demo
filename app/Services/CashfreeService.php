<?php

namespace App\Services;

use Cashfree\Cashfree;
use Cashfree\Model\CreateOrderRequest;
use Cashfree\Model\CustomerDetails;
use Cashfree\Model\OrderMeta;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CashfreeService
{
    protected $cashfree;

    public function __construct()
    {
        $this->cashfree = new Cashfree(
            config('cashfree.environment') === 'production' ? 1 : 0,
            config('cashfree.app_id'),
            config('cashfree.secret_key'),
            "", // XPartnerApiKey
            "", // XPartnerMerchantId
            "", // XClientSignature
            true // XEnableErrorAnalytics
        );
        
        $this->cashfree->XApiVersion = config('cashfree.api_version', '2023-08-01');
    }

    /**
     * Create a new order in Cashfree using SDK
     */
    public function createOrder(array $params)
    {
        $customer_details = new CustomerDetails();
        $customer_details->setCustomerId($params['customer_id'] ?? 'CUST_' . Str::random(10));
        $customer_details->setCustomerPhone($params['customer_phone']);
        $customer_details->setCustomerEmail($params['customer_email']);

        $order_meta = new OrderMeta();
        $order_meta->setReturnUrl(url('/payment-status?order_id={order_id}'));

        $create_order_request = new CreateOrderRequest();
        $create_order_request->setOrderAmount((float) $params['amount']);
        $create_order_request->setOrderCurrency("INR");
        $create_order_request->setCustomerDetails($customer_details);
        $create_order_request->setOrderMeta($order_meta);
        
        $orderId = $params['order_id'] ?? 'ORD_' . time() . Str::random(4);
        $create_order_request->setOrderId($orderId);

        try {
            $result = $this->cashfree->pGCreateOrder($create_order_request);
            
            return [
                'success' => true,
                'data' => $result[0], // OrderEntity
                'status' => $result[1]
            ];
        } catch (\Exception $e) {
            Log::error('Cashfree Create Order Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get order details from Cashfree using SDK
     */
    public function getOrder($orderId)
    {
        try {
            $result = $this->cashfree->pGFetchOrder($orderId);
            return [
                'success' => true,
                'data' => $result[0]
            ];
        } catch (\Exception $e) {
            Log::error('Cashfree Get Order Error: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Verify Webhook Signature using SDK
     */
    public function verifyWebhook($signature, $rawPayload, $timestamp)
    {
        try {
            return $this->cashfree->PGVerifyWebhookSignature($signature, $rawPayload, $timestamp);
        } catch (\Exception $e) {
            Log::warning('Cashfree Webhook Verification Failed: ' . $e->getMessage());
            return false;
        }
    }
}
