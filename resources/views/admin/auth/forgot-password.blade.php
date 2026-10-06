<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Forgot Password — Ferry Booking Platform</title>
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
                              50: '#e8edf5',
                              100: '#c5d1e6',
                              200: '#9eb2d4',
                              300: '#7793c2',
                              400: '#587bb4',
                              500: '#3b64a5',
                              600: '#2e5090',
                              700: '#1d3d72',
                              800: '#152d56',
                              900: '#0e1f3d',
                              950: '#081427',
                            },
                    },
                    animation: {
                        'fade-up': 'fadeUp 0.6s ease-out forwards',
                        'fade-up-delay': 'fadeUp 0.6s 0.15s ease-out forwards',
                    },
                    keyframes: {
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
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        }
        
        .bg-image-overlay {
            position: absolute;
            inset: 0;
            z-index: 0;
            background-image: url('{{ asset('images/bg-mafia-01.jpg') }}');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            opacity: 0.95;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08), 
                        0 0 0 1px rgba(0, 0, 0, 0.02);
        }
        
        .glass-input {
            background: rgba(255, 255, 255, 0.7);
            border: 1px solid rgba(203, 213, 225, 0.5);
            transition: all 0.3s ease;
        }
        
        .glass-input:focus {
            background: rgba(255, 255, 255, 0.95);
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.15);
        }
        
        .glow-btn {
            background: linear-gradient(135deg, #214880, #123362);
            color: #eee;
            box-shadow: 0 4px 15px #2f783433;
            transition: all 0.3s ease;
        }
        
        .glow-btn:hover {
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="h-full font-sans antialiased login-gradient overflow-hidden relative text-slate-800">

    <!-- Background Elements -->
    <div class="bg-image-overlay"></div>

    <div class="flex min-h-full flex-col justify-center py-12 sm:px-6 lg:px-8 relative z-10">
        <!-- Brand -->
        <div class="sm:mx-auto sm:w-full sm:max-w-md animate-fade-up">
            <div class="flex flex-col items-center justify-center gap-4 mb-6">
                <!-- Logo inside card below -->
            </div>
        </div>

        <!-- Card -->
        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-[440px] animate-fade-up-delay opacity-0" style="animation-fill-mode: forwards;">
            <div class="glass-card rounded-2xl px-8 py-10 sm:px-10 relative overflow-hidden">
                <!-- Top accent line -->
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-transparent via-brand-500 to-transparent opacity-50"></div>
                
                <div class="relative flex justify-center w-full mb-2">
                    <img src="{{ asset('images/logo.png') }}" alt="Logo" class="h-28 w-auto object-contain drop-shadow-lg">
                </div>
                
                <h1 class="text-center mt-2 mb-6">
                    <span class="block text-xl font-bold tracking-tight text-slate-900 drop-shadow-sm">Forgot Password</span>
                    <span class="block text-sm text-slate-500 mt-2">Enter your email address and we will send you a password reset link via Email and SMS.</span>
                </h1>

                @if (session('status'))
                    <div class="mb-4 rounded-xl bg-emerald-50 p-4 border border-emerald-200">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-emerald-400"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-emerald-800">
                                    {{ session('status') }}
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <form class="space-y-6" method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-600 mb-2">
                            Email address
                        </label>
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none transition-colors group-focus-within:text-brand-500">
                                <i class="fas fa-envelope text-slate-400 text-sm"></i>
                            </div>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                value="{{ old('email') }}"
                                placeholder="name@example.com"
                                class="glass-input block w-full rounded-xl py-3.5 pl-11 pr-4 text-slate-900 placeholder:text-slate-400 focus:outline-none sm:text-sm @error('email') border-red-400 @enderror">
                        </div>
                        @error('email')
                            <p class="mt-2 text-sm text-red-500 flex items-center gap-1">
                                <i class="fas fa-exclamation-circle text-xs"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Submit -->
                    <div class="pt-2 flex flex-col gap-3">
                        <button type="submit"
                            class="glow-btn flex w-full justify-center items-center gap-2 rounded-xl px-4 py-3.5 text-sm font-bold text-gray-100 hover:text-gray-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-500 transform active:scale-[0.98] transition-all">
                            <span>Send Reset Link</span>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                        
                        <a href="{{ route('login') }}" class="text-center text-sm font-medium text-slate-500 hover:text-slate-700 transition-colors mt-2">
                            <i class="fas fa-arrow-left mr-1"></i> Back to login
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
