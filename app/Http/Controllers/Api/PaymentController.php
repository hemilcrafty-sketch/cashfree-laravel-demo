<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CashfreeService;
use App\Models\Order;

class PaymentController extends Controller
{
    protected $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    public function createOrder(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric',
            'customer_email' => 'required_without:email',
            'email' => 'sometimes|email',
            'customer_phone' => 'required_without:phone',
            'phone' => 'sometimes'
        ]);

        $email = $request->customer_email ?? $request->email;
        $phone = $request->customer_phone ?? $request->phone;

        $orderId = uniqid("order_");

        // Save order
        $order = Order::create([
            'order_id' => $orderId,
            'amount' => $request->amount,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'status' => 'pending'
        ]);

        $response = $this->cashfree->createOrder(
            $orderId,
            $request->amount,
            $email,
            $phone
        );

        return response()->json([
            'order_id' => $orderId,
            'payment_session_id' => $response->payment_session_id ?? null,
            'payment_link' => $response->payment_link ?? null
        ]);
    }

    public function paymentStatus($orderId)
    {
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found'], 404);
        }

        return response()->json([
            'order_id' => $order->order_id,
            'status' => $order->status,
            'amount' => $order->amount,
            'customer' => [
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
            ]
        ]);
    }

    public function verifyPayment($orderId)
    {
        $order = Order::where('order_id', $orderId)->first();

        if (!$order) {
            return response()->json(['error' => 'Order not found locally'], 404);
        }

        try {
            $cfOrder = $this->cashfree->getOrder($orderId);
            
            if (isset($cfOrder->order_status)) {
                $order->status = strtolower($cfOrder->order_status);
                $order->save();
            }

            return response()->json([
                'order_id' => $orderId,
                'status' => $order->status,
                'cashfree_response' => $cfOrder
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function webhook(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');
        $rawData = $request->getContent();

        \Log::info('Cashfree Webhook received', ['data' => $request->all()]);

        // Verify signature if headers are present
        if ($signature && $timestamp) {
            try {
                if (!$this->cashfree->verifySignature($signature, $timestamp, $rawData)) {
                    \Log::error('Cashfree Webhook: Invalid Signature');
                    return response()->json(['error' => 'Invalid signature'], 401);
                }
            } catch (\Exception $e) {
                \Log::error('Cashfree Webhook: Verification Error', ['message' => $e->getMessage()]);
            }
        }

        // Handle both nested (v3) and flat (v2) structures
        $orderId = $request->input('data.order.order_id') ?? $request->input('order_id');
        $status = $request->input('data.payment.payment_status') ?? $request->input('order_status');
        $cfPaymentId = $request->input('data.payment.cf_payment_id') ?? $request->input('cf_payment_id');

        $order = Order::where('order_id', $orderId)->first();

        if ($order) {
            // Cashfree statuses: SUCCESS, FAILED, PENDING, USER_DROPPED
            if (in_array($status, ['SUCCESS', 'PAID'])) {
                $order->status = 'paid';
            } elseif (in_array($status, ['FAILED', 'CANCELLED'])) {
                $order->status = 'failed';
            }
            
            $order->payment_id = $cfPaymentId;
            $order->save();
            
            \Log::info('Order updated via webhook', ['order_id' => $orderId, 'status' => $order->status]);
        }

        return response()->json(['status' => 'ok']);
    }
}
