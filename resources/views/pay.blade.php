<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Cashfree Integration</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; }
        .glass { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); }
        .btn-primary { background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 100%); transition: all 0.3s ease; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4); }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass rounded-3xl shadow-2xl p-8 space-y-8">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-gray-900 tracking-tight">CloudPay Checkout</h1>
            <p class="text-gray-500 mt-2">Safe. Fast. Secure.</p>
        </div>

        @if(session('error'))
            <div class="bg-red-50 text-red-600 p-4 rounded-xl text-sm border border-red-100">
                {{ session('error') }}
            </div>
        @endif

        <form action="{{ route('payment.create') }}" method="POST" class="space-y-6">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" name="customer_email" required 
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                    placeholder="john@example.com">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                <input type="text" name="customer_phone" required maxlength="10"
                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-blue-500 focus:border-transparent outline-none transition-all"
                    placeholder="9876543210">
            </div>

            <div class="bg-blue-50 p-6 rounded-2xl border border-blue-100">
                <div class="flex justify-between items-center">
                    <span class="text-blue-700 font-medium">Total Amount</span>
                    <div class="flex items-center space-x-2">
                        <span class="text-xs text-blue-400 font-bold">INR</span>
                        <input type="number" name="amount" value="1.00" min="1" step="0.01" required
                            class="w-24 bg-transparent text-2xl font-bold text-blue-900 focus:outline-none text-right">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full btn-primary text-white font-bold py-4 rounded-2xl shadow-lg">
                Pay Now
            </button>
        </form>

        <div class="flex items-center justify-center space-x-4 opacity-50 grayscale">
            <img src="https://www.cashfree.com/wp-content/themes/cashfree/images/logo.svg" alt="Cashfree" class="h-4">
        </div>
    </div>
</body>
</html>
