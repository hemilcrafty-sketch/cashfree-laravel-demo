<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Status | Cashfree Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .status-card { transform: scale(1); transition: transform 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275); }
        .status-card:hover { transform: scale(1.02); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-[2rem] shadow-2xl p-8 text-center space-y-6 status-card">
        
        @if($order->status === 'paid')
            <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Payment Successful!</h1>
            <p class="text-gray-500">Your order #{{ $order->order_id }} has been processed successfully.</p>
        @elseif($order->status === 'failed')
            <div class="bg-red-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Payment Failed</h1>
            <p class="text-gray-500 mb-6">Something went wrong with your transaction.</p>
            <a href="{{ route('payment.form') }}" class="inline-block px-6 py-2 bg-red-600 text-white rounded-full text-sm font-bold animate-bounce">
                Try Again
            </a>
        @else
            <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto animate-pulse">
                <svg class="w-10 h-10 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Payment Pending</h1>
            <p class="text-gray-500">We are verifying your payment status...</p>
            <script>setTimeout(() => window.location.reload(), 3000);</script>
        @endif

        <div class="border-t border-gray-100 pt-6 space-y-3 text-left">
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Order ID</span>
                <span class="font-semibold text-gray-800">{{ $order->order_id }}</span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Amount Paid</span>
                <span class="font-semibold text-gray-800">₹{{ number_format($order->amount, 2) }}</span>
            </div>
            @if($order->payment_id)
            <div class="flex justify-between text-sm">
                <span class="text-gray-400">Payment ID</span>
                <span class="font-mono text-xs text-gray-600">{{ $order->payment_id }}</span>
            </div>
            @endif
        </div>

        <div class="pt-4">
            <a href="{{ route('payment.form') }}" class="inline-block w-full bg-gray-900 text-white font-semibold py-4 rounded-2xl hover:bg-black transition-colors">
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
