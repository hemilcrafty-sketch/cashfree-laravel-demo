<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashfree Payment Demo</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Cashfree SDK -->
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Background Gradients -->
    <div class="absolute top-0 -left-48 w-96 h-96 bg-indigo-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse"></div>
    <div class="absolute bottom-0 -right-48 w-96 h-96 bg-pink-600 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-pulse delay-700"></div>

    <div class="max-w-xl w-full relative">
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-indigo-400 to-pink-400 bg-clip-text text-transparent">Cashfree Demo</h1>
            <p class="text-slate-400 text-lg">Integrated with Laravel & Latest API v2023-08-01</p>
        </div>

        <div class="glass p-8 rounded-3xl shadow-2xl">
            <form id="paymentForm" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Customer Name</label>
                        <input type="text" name="name" value="Hemil Patel" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Amount (INR)</label>
                        <input type="number" name="amount" value="1.00" min="1" step="0.01" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-indigo-500 transition-all text-indigo-400 font-bold text-xl">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Email Address</label>
                        <input type="email" name="email" value="hemil@example.com" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-indigo-500 transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Phone Number</label>
                        <input type="tel" name="phone" value="9876543210" pattern="[0-9]{10}" required class="w-full bg-slate-800/50 border border-slate-700 rounded-xl px-4 py-3 outline-none focus:border-indigo-500 transition-all">
                    </div>
                </div>

                <div id="errorMessage" class="hidden p-4 bg-red-500/10 border border-red-500/20 rounded-xl text-red-400 text-sm"></div>

                <button type="submit" id="payButton" class="w-full bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-400 py-4 rounded-xl font-bold text-lg shadow-lg shadow-indigo-600/20 transition-all active:scale-[0.98] flex items-center justify-center gap-3">
                    <span id="buttonText">Pay Securely Now</span>
                    <svg id="loadingSpinner" class="hidden animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </button>
            </form>
        </div>

        <div class="mt-8 flex justify-center gap-6 opacity-40 grayscale transition-all hover:grayscale-0 hover:opacity-100 italic text-sm">
            <span class="flex items-center gap-1">🔒 SSL Secure</span>
            <span class="flex items-center gap-1">✅ 256-bit AES</span>
            <span class="flex items-center gap-1">💳 PCI-DSS Compliant</span>
        </div>
    </div>

    <script>
        const cashfree = Cashfree({ mode: "sandbox" }); 

        document.getElementById('paymentForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = document.getElementById('payButton');
            const btnText = document.getElementById('buttonText');
            const spinner = document.getElementById('loadingSpinner');
            const errorDiv = document.getElementById('errorMessage');
            
            // UI Loading state
            btn.disabled = true;
            btnText.textContent = 'Processing...';
            spinner.classList.remove('hidden');
            errorDiv.classList.add('hidden');

            try {
                // 1. Create Order through Backend
                // Using URL without domain for subfolder compatibility
                const response = await fetch('api/payments/create-order', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        amount: e.target.amount.value,
                        email: e.target.email.value,
                        phone: e.target.phone.value
                    })
                });

                if (response.status === 404) {
                    throw new Error("API Route not found. Ensure you are using the correct URL (e.g. http://localhost:8000).");
                }

                const data = await response.json();

                if (data.status !== 'success') {
                    throw new Error(data.message || 'API Error');
                }

                // 2. Launch Cashfree Checkout
                let checkoutOptions = {
                    paymentSessionId: data.payment_session_id,
                    redirectTarget: "_self", 
                };

                cashfree.checkout(checkoutOptions);

            } catch (err) {
                errorDiv.textContent = err.message;
                errorDiv.classList.remove('hidden');
                btn.disabled = false;
                btnText.textContent = 'Pay Securely Now';
                spinner.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
