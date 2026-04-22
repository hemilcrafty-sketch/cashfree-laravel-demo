<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashfree Laravel Integration | Production Ready</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Outfit', sans-serif; background: #0f172a; }
        .glass { background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
        .animate-glow { animation: glow 4s infinite alternate; }
        @keyframes glow { from { box-shadow: 0 0 20px -5px rgba(99, 102, 241, 0.2); } to { box-shadow: 0 0 40px 5px rgba(99, 102, 241, 0.4); } }
    </style>
</head>
<body class="text-white min-h-screen flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Background Accents -->
    <div class="absolute top-0 -left-48 w-96 h-96 bg-indigo-600 rounded-full mix-blend-multiply filter blur-[128px] opacity-20 animate-pulse"></div>
    <div class="absolute bottom-0 -right-48 w-96 h-96 bg-blue-600 rounded-full mix-blend-multiply filter blur-[128px] opacity-20 animate-pulse delay-700"></div>

    <div class="max-w-xl w-full relative z-10">
        <div class="text-center mb-10">
            <h1 class="text-5xl font-bold mb-3 tracking-tight bg-gradient-to-r from-indigo-300 via-blue-400 to-emerald-400 bg-clip-text text-transparent">
                Cashfree REST API
            </h1>
            <p class="text-slate-400 text-lg font-light">Direct API Integration • Secure • Production Ready</p>
        </div>

        <div class="glass p-10 rounded-[2.5rem] shadow-2xl animate-glow">
            @if(session('error'))
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-2xl text-red-400 text-sm flex items-center gap-3">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('payment.process') }}" method="POST" class="space-y-8">
                @csrf
                <div class="space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider ml-1">Customer Email</label>
                            <input type="email" name="customer_email" value="customer@example.com" required 
                                class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 outline-none focus:border-indigo-500/50 focus:bg-white/10 transition-all text-slate-200">
                        </div>
                        <div class="space-y-2">
                            <label class="text-xs font-semibold text-slate-500 uppercase tracking-wider ml-1">Phone Number</label>
                            <input type="tel" name="customer_phone" value="9876543210" pattern="[0-9]{10}" required 
                                class="w-full bg-white/5 border border-white/10 rounded-2xl px-5 py-4 outline-none focus:border-indigo-500/50 focus:bg-white/10 transition-all text-slate-200">
                        </div>
                    </div>

                    <div class="bg-indigo-500/10 p-8 rounded-3xl border border-indigo-500/20 group hover:border-indigo-500/40 transition-all">
                        <div class="flex justify-between items-center">
                            <div>
                                <h3 class="text-indigo-300 font-semibold mb-1">Service Amount</h3>
                                <p class="text-slate-500 text-xs uppercase tracking-widest">Payable in INR</p>
                            </div>
                            <div class="flex items-baseline gap-2">
                                <span class="text-slate-400 text-sm">₹</span>
                                <input type="number" name="amount" value="1.00" min="1" step="0.01" required 
                                    class="w-24 bg-transparent text-3xl font-bold text-white text-right focus:outline-none">
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 via-blue-600 to-indigo-600 bg-[length:200%_auto] hover:bg-right py-5 rounded-2xl font-bold text-lg shadow-xl shadow-indigo-500/20 transition-all duration-500 active:scale-[0.98] flex items-center justify-center gap-3">
                    Start Secure Payment
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path></svg>
                </button>
            </form>
        </div>

        <div class="mt-12 flex flex-col items-center gap-4 opacity-40">
            <div class="flex gap-8 text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400">
                <span>AES-256 Bit</span>
                <span>ISO 27001</span>
                <span>PCI DSS</span>
            </div>
            <img src="https://cashfreelogo.cashfree.com/website/landings/homepage/cashfreeLogo.png " alt="Cashfree" class="h-4">
        </div>
    </div>
</body>
</html>
