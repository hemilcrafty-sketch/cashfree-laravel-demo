<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CashfreeService;
use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    /**
     * Create Order & Initiate Payment
     */
    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'customer_email' => 'required_without:email|email',
            'email' => 'sometimes|email',
            'customer_phone' => 'required_without:phone',
            'phone' => 'sometimes'
        ]);

        $email = $request->customer_email ?? $request->email;
        $phone = $this->sanitizePhone($request->customer_phone ?? $request->phone);

        // Generate a clean, unique Order ID
        $orderId = 'ORD_' . strtoupper(uniqid());

        // Create local order record
        $order = Order::create([
            'order_id' => $orderId,
            'amount' => $request->amount,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'status' => 'pending'
        ]);

        try {
            $cfResponse = $this->cashfree->createOrder($orderId, $request->amount, $email, $phone);
            
            if ($cfResponse && isset($cfResponse->cf_order_id)) {
                // Store Cashfree's internal Order ID
                $order->update(['cf_order_id' => $cfResponse->cf_order_id]);
                
                return response()->json([
                    'order_id' => $orderId,
                    'cf_order_id' => $cfResponse->cf_order_id,
                    'payment_session_id' => $cfResponse->payment_session_id ?? null,
                    'payment_link' => $cfResponse->payment_link ?? null
                ]);
            }
            
            throw new \Exception("Invalid response from payment gateway.");
        } catch (\Exception $e) {
            Log::error('Order Creation Error: ' . $e->getMessage());
            $order->update(['status' => 'failed']);
            return response()->json(['error' => 'Could not initiate payment. Please try again.'], 500);
        }
    }

    /**
     * Verify Payment Status (Redirection Handler)
     * GET /api/payments/verify/{orderId}
     */
    public function verifyPayment($orderId)
    {
        $order = Order::where('order_id', $orderId)->firstOrFail();

        // Always fetch the "Source of Truth" from Cashfree API
        $cfOrder = $this->cashfree->getOrder($orderId);

        if ($cfOrder && isset($cfOrder->order_status)) {
            $this->updateOrderStatus($order, $cfOrder->order_status, $cfOrder->cf_order_id ?? null);
        }

        return view('payment-status', compact('order'));
    }

    /**
     * Webhook Handler (Server-to-Server)
     * POST /api/payments/webhook
     */
    public function webhook(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');
        $rawData = $request->getContent();

        Log::info('Cashfree Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->all()]);

        // 1. Security Check: Verify Signature
        if (!$signature || !$timestamp || !$this->cashfree->verifySignature($signature, $timestamp, $rawData)) {
            Log::warning('Cashfree Webhook: INVALID SIGNATURE');
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // 2. Extract Data (Handles both v2 and v3 payloads)
        $orderId = $request->input('data.order.order_id') ?? $request->input('order_id');
        $status = $request->input('data.payment.payment_status') ?? $request->input('order_status');
        $cfPaymentId = $request->input('data.payment.cf_payment_id') ?? $request->input('cf_payment_id');

        $order = Order::where('order_id', $orderId)->first();

        if ($order) {
            $this->updateOrderStatus($order, $status, null, $cfPaymentId);
            return response()->json(['status' => 'acknowledged']);
        }

        return response()->json(['message' => 'Order not found'], 404);
    }

    /**
     * Internal helper to update order status (Idempotent)
     */
    private function updateOrderStatus(Order $order, $status, $cfOrderId = null, $paymentId = null)
    {
        // IDEMPOTENCY: Don't update if already marked as paid
        if ($order->status === 'paid') {
            return;
        }

        $newStatus = 'pending';
        $status = strtoupper($status);

        if (in_array($status, ['PAID', 'SUCCESS'])) {
            $newStatus = 'paid';
        } elseif (in_array($status, ['FAILED', 'CANCELLED', 'FLAGGED'])) {
            $newStatus = 'failed';
        }

        $updateData = ['status' => $newStatus];
        if ($cfOrderId) $updateData['cf_order_id'] = $cfOrderId;
        if ($paymentId) $updateData['payment_id'] = $paymentId;

        $order->update($updateData);
        
        Log::info("Order #{$order->order_id} status updated to: {$newStatus}");
    }

    /**
     * Sanitize phone number to digits only
     */
    private function sanitizePhone($phone)
    {
        return preg_replace('/[^0-9]/', '', $phone);
    }
}
