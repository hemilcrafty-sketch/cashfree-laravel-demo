<?php

namespace App\Http\Controllers;

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
     * Show Payment Form
     */
    public function showPaymentForm()
    {
        return view('pay');
    }

    /**
     * Create Order & Redirect to Cashfree Checkout
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'customer_email' => 'required|email',
            'customer_phone' => 'required|digits:10',
        ]);

        try {
            $orderId = 'ORD_' . time();

            $result = $this->cashfree->createOrder([
                'order_id' => $orderId,
                'amount' => $validated['amount'],
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
            ]);

            if (!$result['success']) {
                return back()->with('error', 'Payment Initiation Failed: ' . $result['message']);
            }

            $orderData = $result['data']; // OrderEntity

            // Store Order in Database
            Order::create([
                'order_id' => $orderId,
                'amount' => $validated['amount'],
                'status' => 'pending',
                'customer_email' => $validated['customer_email'],
                'customer_phone' => $validated['customer_phone'],
                'payment_session_id' => $orderData->getPaymentSessionId(),
            ]);

            // Redirect to Hosted Checkout
            $env = config('cashfree.environment');
            $checkoutUrl = ($env === 'production')
                ? "https://payments.cashfree.com/order/" . $orderData->getPaymentSessionId()
                : "https://sandbox.cashfree.com/pg/view/checkout?session_id=" . $orderData->getPaymentSessionId();

            return redirect()->away($checkoutUrl);

        } catch (\Exception $e) {
            Log::error('Create Order Controller Error: ' . $e->getMessage());
            return back()->with('error', 'Something went wrong. Please try again.');
        }
    }

    /**
     * Handle Secure Webhook from Cashfree
     */
    public function handleWebhook(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $timestamp = $request->header('x-webhook-timestamp');
        $rawPayload = $request->getContent();

        Log::info('Cashfree Webhook Received', ['headers' => $request->headers->all(), 'body' => $request->all()]);

        // Secure Signature Verification using SDK
        $event = $this->cashfree->verifyWebhook($signature, $rawPayload, $timestamp);
        if (!$event) {
            Log::warning('Cashfree Webhook: Invalid Signature Detected');
            return response()->json(['message' => 'Invalid Signature'], 400);
        }

        $payload = $event->object;
        $data = $payload->data;
        $orderId = $data->order->order_id;
        $paymentStatus = $data->payment->payment_status;
        $cfPaymentId = $data->payment->cf_payment_id;

        $order = Order::where('order_id', $orderId)->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // Prevent Duplicate Processing
        if ($order->status === 'paid') {
            return response()->json(['message' => 'Order already processed']);
        }

        $newStatus = match ($paymentStatus) {
            'SUCCESS' => 'paid',
            'FAILED', 'CANCELLED' => 'failed',
            default => 'pending'
        };

        $order->update([
            'status' => $newStatus,
            'payment_id' => $cfPaymentId,
        ]);

        Log::info('Order Status Updated via Webhook', ['order_id' => $orderId, 'status' => $newStatus]);

        return response()->json(['status' => 'success']);
    }

    /**
     * Sync and Show Payment Status (After Redirect)
     */
    public function paymentStatus(Request $request)
    {
        $orderId = $request->query('order_id');
        if (!$orderId) {
            return redirect('/')->with('error', 'Order ID missing.');
        }

        $order = Order::where('order_id', $orderId)->firstOrFail();
        
        // Fallback: Sync status directly with API if still pending (webhook might have delay)
        if ($order->status === 'pending') {
            $this->syncOrderStatus($orderId);
            $order->refresh();
        }

        return view('payment-status', compact('order'));
    }

    /**
     * API endpoint to check payment status (Public API)
     */
    public function verifyPayment($orderId)
    {
        $this->syncOrderStatus($orderId);
        $order = Order::where('order_id', $orderId)->firstOrFail();
        
        return response()->json([
            'status' => 'success',
            'order_id' => $orderId,
            'payment_status' => $order->status,
            'payment_id' => $order->payment_id
        ]);
    }

    /**
     * Internal Helper to Sync Order Status from Cashfree API
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
            return $status;
        }
        return 'error';
    }
}
