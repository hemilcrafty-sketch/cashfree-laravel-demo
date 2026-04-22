<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout | Cashfree Payment</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script src="https://sdk.cashfree.com/js/v3/cashfree.js"></script>
    <style>
        :root {
            --primary: #6366f1;
            --primary-hover: #4f46e5;
            --bg: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --text: #f8fafc;
            --text-muted: #94a3b8;
            --error: #ef4444;
            --success: #22c55e;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg);
            background-image: 
                radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.15) 0px, transparent 50%),
                radial-gradient(at 100% 100%, rgba(168, 85, 247, 0.15) 0px, transparent 50%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .checkout-container {
            width: 100%;
            max-width: 450px;
            background: var(--card-bg);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 8px;
            background: linear-gradient(to right, #818cf8, #c084fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-muted);
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 400;
            margin-bottom: 8px;
            color: var(--text-muted);
        }

        .input-wrapper {
            position: relative;
        }

        input {
            width: 100%;
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            color: white;
            font-size: 16px;
            transition: all 0.3s ease;
            outline: none;
        }

        input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
            background: rgba(15, 23, 42, 0.8);
        }

        .pay-button {
            width: 100%;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .pay-button:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.4);
        }

        .pay-button:active {
            transform: translateY(0);
        }

        .pay-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .loader {
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            display: none;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .error-message {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: var(--error);
            padding: 12px;
            border-radius: 12px;
            font-size: 14px;
            margin-bottom: 20px;
            display: none;
        }

        .footer {
            margin-top: 32px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-size: 12px;
            color: var(--text-muted);
        }

        .footer svg {
            width: 16px;
            height: 16px;
        }
    </style>
</head>
<body>

    <div class="checkout-container">
        <div class="header">
            <h1>Secure Checkout</h1>
            <p>Complete your payment via Cashfree</p>
        </div>

        <div id="errorBox" class="error-message"></div>

        <form id="paymentForm">
            <div class="form-group">
                <label for="amount">Amount (INR)</label>
                <input type="number" id="amount" value="1.00" step="0.01" min="1" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" placeholder="customer@example.com" required>
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" placeholder="9876543210" required>
            </div>

            <button type="submit" class="pay-button" id="payBtn">
                <span class="loader" id="loader"></span>
                <span id="btnText">Pay Now</span>
            </button>
        </form>

        <div class="footer">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="Wait, I'll use a lock icon"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
            </svg>
            Secured by Cashfree Payments
        </div>
    </div>

    <script>
        // Initialize Cashfree
        const cashfree = Cashfree({
            mode: "{{ config('services.cashfree.env') === 'production' ? 'production' : 'sandbox' }}"
        });

        const paymentForm = document.getElementById('paymentForm');
        const payBtn = document.getElementById('payBtn');
        const loader = document.getElementById('loader');
        const btnText = document.getElementById('btnText');
        const errorBox = document.getElementById('errorBox');

        paymentForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Reset UI
            errorBox.style.display = 'none';
            payBtn.disabled = true;
            loader.style.display = 'block';
            btnText.innerText = 'Processing...';

            const payload = {
                amount: document.getElementById('amount').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value
            };

            try {
                // 1. Call your Laravel API to create the order
                const response = await fetch('/api/payments/create-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.error || 'Failed to initiate payment');
                }

                console.log("Order Created:", data);

                // 2. Open Cashfree Checkout Modal
                let checkoutOptions = {
                    paymentSessionId: data.payment_session_id,
                    redirectTarget: "_self", // _self is recommended for seamless flow
                };

                cashfree.checkout(checkoutOptions);

            } catch (error) {
                console.error("Payment Error:", error);
                errorBox.innerText = error.message;
                errorBox.style.display = 'block';
                
                payBtn.disabled = false;
                loader.style.display = 'none';
                btnText.innerText = 'Pay Now';
            }
        });
    </script>
</body>
</html>
