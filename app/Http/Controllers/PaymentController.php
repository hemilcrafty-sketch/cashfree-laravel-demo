<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\CashfreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected $cashfree;

    public function __construct(CashfreeService $cashfree)
    {
        $this->cashfree = $cashfree;
    }

    /**
     * Create Order
     */
    public function createOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'email' => 'required|email',
            'phone' => 'required|digits:10',
        ]);

        try {
            $result = $this->cashfree->createOrder($validated);

            if (!$result['success']) {
                return response()->json([
                    'status' => 'error',
                    'message' => $result['data']['message'] ?? 'Cashfree API Error'
                ], $result['status']);
            }

            $data = $result['data'];

            // Store in Database
            Payment::create([
                'order_id' => $data['order_id'],
                'amount' => $validated['amount'],
                'status' => 'pending',
                'raw_response' => $data
            ]);

            return response()->json([
                'status' => 'success',
                'order_id' => $data['order_id'],
                'payment_session_id' => $data['payment_session_id']
            ]);

        } catch (\Exception $e) {
            Log::error('Create Order Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Verify Payment (Polling / Sync check)
     */
    public function verifyPayment($orderId)
    {
        try {
            $result = $this->cashfree->getOrder($orderId);

            if (!$result['success']) {
                return response()->json(['status' => 'error', 'message' => 'Order not found'], 404);
            }

            $data = $result['data'];
            $cfStatus = strtoupper($data['order_status']);
            
            $finalStatus = match ($cfStatus) {
                'PAID' => 'success',
                'FAILED', 'EXPIRED' => 'failed',
                default => 'pending'
            };

            // Update DB if needed
            $payment = Payment::where('order_id', $orderId)->first();
            if ($payment && $payment->status !== 'success') {
                $payment->update([
                    'status' => $finalStatus,
                    'raw_response' => $data
                ]);
            }

            return response()->json([
                'status' => 'success',
                'order_id' => $orderId,
                'payment_status' => $finalStatus,
                'cf_status' => $cfStatus
            ]);

        } catch (\Exception $e) {
            Log::error('Verify Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Verification failed'], 500);
        }
    }

    /**
     * Handle Webhook
     */
    public function handleWebhook(Request $request)
    {
        $signature = $request->header('x-webhook-signature');
        $rawPayload = $request->getContent();

        Log::info('Cashfree Webhook Received', ['payload' => $request->all()]);

        /* -------------------------------------------------------------------------- */
        /*                          PRODUCTION READY VERIFICATION                     */
        /* -------------------------------------------------------------------------- */
        /*
        // Signature Verification: Uncomment this for Production
        if (!$this->cashfree->verifySignature($signature, $rawPayload)) {
            Log::warning('Cashfree Webhook Signature Mismatch');
            return response()->json(['message' => 'Invalid Signature'], 400);
        }
        */
        /* -------------------------------------------------------------------------- */


        $payload = $request->input('data');
        $orderId = $payload['order']['order_id'] ?? null;
        $paymentStatus = $payload['payment']['payment_status'] ?? null;
        $cfPaymentId = $payload['payment']['cf_payment_id'] ?? null;

        if (!$orderId) return response()->json(['message' => 'No Order ID'], 400);

        $payment = Payment::where('order_id', $orderId)->first();
        if (!$payment) return response()->json(['message' => 'Payment record not found'], 404);

        // Don't downgrade status if already success
        if ($payment->status === 'success') {
            return response()->json(['message' => 'Already processed']);
        }

        $finalStatus = ($paymentStatus === 'SUCCESS') ? 'success' : 'failed';

        $payment->update([
            'status' => $finalStatus,
            'payment_id' => $cfPaymentId,
            'raw_response' => $request->all()
        ]);

        return response()->json(['status' => 'success', 'message' => 'Status Updated']);
    }

    /**
     * Show Local Payment Record
     */
    public function showPayment($orderId)
    {
        $payment = Payment::where('order_id', $orderId)->firstOrFail();
        return response()->json([
            'status' => 'success',
            'data' => $payment
        ]);
    }

    /**
     * Generate Test Signature Helper
     */
    public function generateTestSignature(Request $request)
    {
        $payload = $request->getContent();
        $secretKey = config('cashfree.secret_key');
        $signature = base64_encode(hash_hmac('sha256', $payload, $secretKey, true));
        
        return response()->json([
            'status' => 'success',
            'signature' => $signature
        ]);
    }

    /**
     * Simulate Webhook (Generates Sig + Sends Request)
     */
    public function simulateWebhook(Request $request)
    {
        // Use JSON body of the simulator request as-is
        $jsonPayload = $request->getContent(); 
        $payload = $request->all();
        
        $secretKey = config('cashfree.secret_key');
        $signature = base64_encode(hash_hmac('sha256', $jsonPayload, $secretKey, true));
        
        // INTERNALLY trigger the handleWebhook with the EXACT body
        $response = Http::withHeaders([
            'x-webhook-signature' => $signature,
        ])->withBody($jsonPayload, 'application/json')
          ->post(url('/api/payments/webhook'));

        return response()->json([
            'status' => 'success',
            'generated_signature' => $signature,
            'webhook_response' => $response->json(),
            'webhook_status' => $response->status()
        ]);
    }

    /**
     * Display Payment Status (For Redirect)
     */
    public function paymentStatus(Request $request)
    {
        $orderId = $request->query('order_id');
        
        if (!$orderId) return redirect('/');

        $payment = Payment::where('order_id', $orderId)->firstOrFail();
        
        // Sync with Cashfree once more to be sure
        $this->verifyPayment($orderId);
        $payment->refresh();

        return view('payment-status', [
            'payment' => $payment
        ]);
    }
}
