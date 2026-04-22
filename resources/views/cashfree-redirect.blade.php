<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecting to Payment Gateway...</title>
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        body { background: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; font-family: sans-serif; color: white; }
        .loader { border: 4px solid rgba(255, 255, 255, 0.1); border-top: 4px solid #6366f1; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <div style="text-align: center;">
        <div class="loader"></div>
        <p>Connecting to Cashfree Secure Checkout...</p>
    </div>

    <script>
        console.log("Cashfree Session ID:", "{{ $paymentSessionId }}");
        
        const cashfree = Cashfree({
            mode: "{{ config('services.cashfree.env') === 'production' ? 'production' : 'sandbox' }}"
        });

        cashfree.checkout({
            paymentSessionId: "{{ $paymentSessionId }}",
            redirectTarget: "_self"
        });
    </script>
</body>
</html>
