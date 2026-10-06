<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Force Password Change — Ferry Booking Platform</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            400: '#60a5fa', // Blue 400
                            500: '#3b82f6', // Blue 500
                            600: '#2563eb', // Blue 600
                            700: '#1d4ed8',
                        },
                        amber: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        cyan: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        teal: {
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        },
                        surface: {
                            800: '#1e293b',
                            900: '#0f172a', // Deep Blue
                            950: '#020617',
                        },
                    },
                    animation: {
                        'float': 'float 20s infinite ease-in-out',
                        'fade-up': 'fadeUp 0.6s ease-out forwards',
                        'fade-up-delay': 'fadeUp 0.6s 0.15s ease-out forwards',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': { transform: 'translate(0, 0) scale(1)' },
                            '33%': { transform: 'translate(30px, -30px) scale(1.05)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.95)' },
                        },
                        fadeUp: {
                            'from': { opacity: '0', transform: 'translateY(20px)' },
                            'to': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                },
            },
        }
    </script>

    <style>
        .login-gradient {
            /* Deep Blue gradient base */
            background: linear-gradient(135deg, #020617 0%, #0f172a 100%);
        }
        /* Blueprint Pattern Overlay */
        .blueprint-grid {
            background-color: transparent;
            background-image: linear-gradient(#1e293b 1px, transparent 1px),
            linear-gradient(90deg, #1e293b 1px, transparent 1px);
            background-size: 40px 40px;
            opacity: 0.1;
        }
        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
        }
        .glass-input {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s ease;
        }
        .glass-input:focus {
            background: rgba(30, 41, 59, 0.8);
            border-color: #fbbf24;
            box-shadow: 0 0 0 2px rgba(251, 191, 36, 0.2);
        }
        .glow-btn {
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            color: #0f172a; /* Dark text for contrast on gold */
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
            transition: all 0.3s ease;
        }
        .glow-btn:hover {
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.5);
            transform: translateY(-1px);
        }

        /* Animated bg circles */
        .bg-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(120px);
            opacity: 0.2;
            z-index: 0;
            animation: float 20s infinite ease-in-out;
        }
        .bg-orb-1 {
            width: 600px; height: 600px;
            background: #1e3a8a; /* Blue orb */
            top: -10%; left: -10%;
            animation-delay: 0s;
        }
        .bg-orb-2 {
            width: 500px; height: 500px;
            background: #b45309; /* Deep Gold/Amber orb */
            bottom: -10%; right: -10%;
            animation-delay: -7s;
        }
    </style>
</head>
<body class="h-full font-sans antialiased login-gradient overflow-hidden relative">
    <!-- Background Elements -->
    <div class="absolute inset-0 blueprint-grid z-0"></div>
    <div class="bg-orb bg-orb-1"></div>
    <div class="bg-orb bg-orb-2"></div>

    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 relative z-10">
        <!-- Brand -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md animate-fade-up">
            <div class="flex flex-col items-center justify-center gap-4 mb-6">
                <!-- Logo Container -->
                <div class="relative flex justify-center w-full mb-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Ferry Booking Platform Logo" class="h-28 w-auto object-contain drop-shadow-2xl">
                </div>
                
                <h1 class="text-center mt-2">
                    <span class="block text-3xl font-extrabold tracking-tight text-white drop-shadow-lg">Ferry<span class="text-brand-400">Book</span></span>
                    <span class="block text-xs font-semibold text-brand-400 tracking-[0.2em] uppercase mt-1">Booking Platform</span>
                </h1>
            </div>
        </div>

        <!-- Login Card -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-[440px] animate-fade-up-delay opacity-0" style="animation-fill-mode: forwards;">
            <div class="glass-card rounded-2xl px-8 py-10 sm:px-10 relative overflow-hidden">
                <!-- Top accent line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-brand-500 to-transparent opacity-50"></div>

                <form class="space-y-6" method="POST" action="{{ route('admin.password.update') }}">
                    @csrf

                    @if(session('error'))
                        <div class="rounded-xl bg-red-500/10 border border-red-500/50 p-4">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-exclamation-triangle text-red-400"></i>
                                <p class="text-sm font-medium text-red-400">{{ session('error') }}</p>
                            </div>
                        </div>
                    @endif

                    <div class="mb-4">
                        <p class="text-sm text-gray-300 text-center">
                            For security reasons, you must change your password before continuing.
                        </p>
                    </div>

                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-400 mb-2">
                            Current Password
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-400">
                                <i class="fas fa-unlock text-gray-500 text-sm"></i>
                            </div>
                            <input id="current_password" name="current_password" type="password" required
                                placeholder="••••••••"
                                class="glass-input block w-full rounded-xl py-3.5 pl-11 pr-4 text-white placeholder:text-gray-600 focus:outline-none sm:text-sm @error('current_password') border-red-500/50 @enderror">
                        </div>
                        @error('current_password')
                            <p class="mt-2 text-sm text-red-400 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-400 mb-2">
                            New Password
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-400">
                                <i class="fas fa-lock text-gray-500 text-sm"></i>
                            </div>
                            <input id="new_password" name="new_password" type="password" required
                                placeholder="••••••••"
                                class="glass-input block w-full rounded-xl py-3.5 pl-11 pr-4 text-white placeholder:text-gray-600 focus:outline-none sm:text-sm @error('new_password') border-red-500/50 @enderror">
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Min 8 characters, uppercase, lowercase, numbers & symbols.</p>
                        @error('new_password')
                            <p class="mt-2 text-sm text-red-400 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Confirm Password -->
                    <div>
                        <label for="new_password_confirmation" class="block text-sm font-medium text-gray-400 mb-2">
                            Confirm New Password
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-400">
                                <i class="fas fa-lock text-gray-500 text-sm"></i>
                            </div>
                            <input id="new_password_confirmation" name="new_password_confirmation" type="password" required
                                placeholder="••••••••"
                                class="glass-input block w-full rounded-xl py-3.5 pl-11 pr-4 text-white placeholder:text-gray-600 focus:outline-none sm:text-sm">
                        </div>
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 flex flex-col gap-3">
                        <button type="submit"
                            class="glow-btn flex w-full justify-center items-center gap-2 rounded-xl px-4 py-3.5 text-sm font-bold text-surface-900 hover:text-black focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 transform active:scale-[0.98] transition-all">
                            <span>Change Password & Continue</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                        
                        <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-sm text-gray-400 hover:text-white text-center w-full block transition-colors">
                            Sign out instead
                        </a>
                    </div>
                </form>
                
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>

            <div class="mt-8 text-center space-y-2">
                <p class="text-xs text-gray-600">
                    &copy; {{ date('Y') }} Ferry Booking Platform. All rights reserved.
                </p>
                <div class="flex justify-center gap-4 text-gray-700 text-xs">
                    <a href="#" class="hover:text-brand-400 transition-colors">Privacy Policy</a>
                    <span>&bull;</span>
                    <a href="#" class="hover:text-brand-400 transition-colors">Terms of Service</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
