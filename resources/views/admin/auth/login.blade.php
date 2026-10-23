<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In — Ferry Booking Platform</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { theme: { extend: { fontFamily: { sans: ['Outfit', 'sans-serif'] } } } }</script>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Outfit', sans-serif; }
        .login-bg { background: linear-gradient(160deg, #060f1e 0%, #0a1628 40%, #0c1e38 100%); position: fixed; inset: 0; overflow: hidden; }
        .bg-image { position: absolute; inset: 0; background-image: url('{{ asset('images/bg-mafia-01.jpg') }}'); background-size: cover; background-position: center; opacity: 0.08; }
        .orb { position: absolute; border-radius: 50%; filter: blur(100px); animation: drift 18s ease-in-out infinite; }
        .orb-1 { width: 500px; height: 500px; background: rgba(250,189,0,0.07); top: -15%; left: -15%; animation-delay: 0s; }
        .orb-2 { width: 400px; height: 400px; background: rgba(37,99,235,0.08); bottom: -10%; right: -10%; animation-delay: -6s; }
        .orb-3 { width: 300px; height: 300px; background: rgba(52,211,153,0.06); top: 50%; left: 60%; animation-delay: -12s; }
        @keyframes drift { 0%,100% { transform: translate(0,0) scale(1); } 33% { transform: translate(25px,-20px) scale(1.04); } 66% { transform: translate(-15px,15px) scale(0.97); } }
        .login-card { background: rgba(255,255,255,0.97); backdrop-filter: blur(20px); border: 1px solid rgba(255,255,255,0.5); box-shadow: 0 32px 64px -12px rgba(0,0,0,0.35), 0 0 0 1px rgba(0,0,0,0.04); animation: slideUp 0.5s ease-out; }
        @keyframes slideUp { from { opacity: 0; transform: translateY(24px); } to { opacity: 1; transform: translateY(0); } }
        .form-input { background: #f8fafc; border: 1.5px solid #e2e8f0; transition: all 0.25s ease; font-family: 'Outfit', sans-serif; }
        .form-input:focus { background: #fff; border-color: #FABD00; box-shadow: 0 0 0 3px rgba(250,189,0,0.15); outline: none; }
        .form-input.error { border-color: #f87171; }
        .btn-signin { background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%); box-shadow: 0 4px 15px rgba(15,23,42,0.4); transition: all 0.25s ease; font-family: 'Outfit', sans-serif; }
        .btn-signin:hover { box-shadow: 0 6px 24px rgba(250,189,0,0.3); transform: translateY(-1px); }
        .btn-signin:active { transform: translateY(0); }
    </style>
</head>
<body class="h-full antialiased">
    <div class="login-bg">
        <div class="bg-image"></div>
        <div class="orb orb-1"></div>
        <div class="orb orb-2"></div>
        <div class="orb orb-3"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center py-10 px-4">
        <div class="login-card w-full max-w-[420px] rounded-2xl overflow-hidden">
            <div class="h-1 w-full" style="background: linear-gradient(90deg, #FABD00, #f59e0b, #FABD00)"></div>
            <div class="px-8 pt-7 pb-8">

                <div class="flex flex-col items-center mb-7">
                    <div class="flex justify-center mb-3">
                        <img src="{{ asset('images/logo.png') }}" alt="Mafia Fast Ferries"
                            class="h-20 w-auto object-contain drop-shadow-sm"
                            onerror="this.style.display='none'">
                    </div>
                    <p class="text-xs font-bold uppercase tracking-[0.2em] text-slate-400 mt-1">Booking Platform</p>
                </div>

                @if($errors->any())
                <div class="mb-5 rounded-xl bg-red-50 border border-red-200 px-4 py-3 flex items-start gap-2.5">
                    <i class="fas fa-exclamation-circle text-red-500 mt-0.5 text-sm shrink-0"></i>
                    <div>@foreach($errors->all() as $e)<p class="text-sm text-red-700 font-medium">{{ $e }}</p>@endforeach</div>
                </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}" class="space-y-4">
                    @csrf
                    <div class="space-y-1.5">
                        <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Email Address</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-slate-400 text-sm"></i>
                            </div>
                            <input id="email" name="email" type="email" required autocomplete="email"
                                value="{{ old('email') }}" placeholder="admin@platform.co.tz"
                                class="form-input w-full rounded-xl pl-10 pr-4 py-3 text-slate-900 text-sm placeholder:text-slate-400 @error('email') error @enderror">
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">Password</label>
                            <a href="{{ route('password.request') }}" class="text-xs font-semibold text-amber-600 hover:text-amber-500 transition-colors">Forgot password?</a>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-slate-400 text-sm"></i>
                            </div>
                            <input id="password" name="password" type="password" required autocomplete="current-password"
                                placeholder="••••••••"
                                class="form-input w-full rounded-xl pl-10 pr-4 py-3 text-slate-900 text-sm @error('password') error @enderror">
                        </div>
                    </div>

                    <label class="flex items-center gap-2.5 cursor-pointer select-none">
                        <input id="remember" name="remember" type="checkbox"
                            class="h-4 w-4 rounded border-slate-300 cursor-pointer" style="accent-color: #FABD00">
                        <span class="text-sm text-slate-600 hover:text-slate-800">Remember me</span>
                    </label>

                    <div class="pt-1">
                        <button type="submit"
                            class="btn-signin w-full flex items-center justify-center gap-2.5 rounded-xl px-4 py-3.5 text-sm font-bold text-white tracking-wide">
                            <span>Sign In</span>
                            <i class="fas fa-arrow-right-to-bracket text-amber-400"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="mt-8 text-center space-y-2">
            <p class="text-xs" style="color: rgba(255,255,255,0.25)">&copy; {{ date('Y') }} Ferry Booking Platform. All rights reserved.</p>
            <div class="flex justify-center gap-4 text-xs" style="color: rgba(255,255,255,0.2)">
                <a href="#" class="hover:text-white transition-colors">Privacy Policy</a>
                <span>&bull;</span>
                <a href="#" class="hover:text-white transition-colors">Terms of Service</a>
            </div>
        </div>
    </div>
</body>
</html>