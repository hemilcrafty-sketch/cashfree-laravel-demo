<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CashfreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    /**
     * Create Order API
     * POST /api/create-order
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|digits:10',
        ]);

        try {
            // Internal unique Order ID
            $orderId = 'ORD_' . strtoupper(bin2hex(random_bytes(4)));

            $result = $this->cashfree->createOrder([
                'order_id' => $orderId,
                'amount' => $validated['amount'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
            ]);

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment Initiation Failed',
                    'details' => $result['message']
                ], 400);
            }

            $orderData = $result['data']; // OrderEntity

            // Save to DB before returning response
            Order::create([
                'order_id' => $orderId,
                'amount' => $validated['amount'],
                'status' => 'pending',
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'payment_session_id' => $orderData->getPaymentSessionId(),
            ]);

            return response()->json([
                'order_id' => $orderId,
                'payment_session_id' => $orderData->getPaymentSessionId(),
                'payment_link' => $this->getCheckoutUrl($orderData->getPaymentSessionId())
            ]);

        } catch (\Exception $e) {
            Log::error('API Create Order Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Check Payment Status API
     * GET /api/payment-status/{order_id}
     */
    public function paymentStatus($orderId)
    {
        $order = Order::where('order_id', $orderId)->first();
        
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Sync with Cashfree once more to ensure status is up to date
        $this->syncOrderStatus($orderId);
        $order->refresh();

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status, // paid, pending, failed
            'payment_id' => $order->payment_id,
            'amount' => $order->amount
        ]);
    }

    /**
     * Webhook Handler API
     * POST /api/cashfree/webhook
     */
    public function handleWebhook(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');
        $rawPayload = $request->getContent();

        Log::info('Cashfree API Webhook Received', ['payload' => $request->all()]);

        // Secure Signature Verification
        $event = $this->cashfree->verifyWebhook($signature, $rawPayload, $timestamp);
        if (!$event) {
            Log::warning('Webhook Signature Mismatch');
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $payload = $event->object;
        $data = $payload->data;
        $orderId = $data->order->order_id;
        $paymentStatus = $data->payment->payment_status;
        $cfPaymentId = $data->payment->cf_payment_id;

        $order = Order::where('order_id', $orderId)->first();
        if ($order && $order->status !== 'paid') {
            $newStatus = ($paymentStatus === 'SUCCESS') ? 'paid' : 'failed';
            $order->update([
                'status' => $newStatus,
                'payment_id' => $cfPaymentId
            ]);
        }

        return response()->json(['status' => 'success']);
    }

    /**
     * Construct Checkout URL for Sandbox/Production
     */
    protected function getCheckoutUrl($sessionId)
    {
        $env = config('cashfree.environment');
        return ($env === 'production')
            ? "https://payments.cashfree.com/order/$sessionId"
            : "https://sandbox.cashfree.com/pg/view/checkout?session_id=$sessionId";
    }

    /**
     * Sync order status with Cashfree API
     */
    protected function syncOrderStatus($orderId)
    {
        $result = $this->cashfree->getOrder($orderId);
        if ($result['success']) {
            $orderData = $result['data'];
            $cfStatus = $orderData->getOrderStatus();
            $status = match ($cfStatus) {
                'PAID' => 'paid',
                'FAILED', 'EXPIRED', 'TERMINATED' => 'failed',
                default => 'pending'
            };
            Order::where('order_id', $orderId)->update(['status' => $status]);
        }
    }
}
