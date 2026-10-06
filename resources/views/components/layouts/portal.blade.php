<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Booking' }} — Ferry Flow Portal</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: {
                fontFamily: { sans: ['Inter', 'sans-serif'] },
                colors: {
                    brand: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554' },
                    amber: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554' },
                    cyan: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554' },
                    teal: { 50:'#eff6ff',100:'#dbeafe',200:'#bfdbfe',300:'#93c5fd',400:'#60a5fa',500:'#3b82f6',600:'#2563eb',700:'#1d4ed8',800:'#1e40af',900:'#1e3a8a',950:'#172554' },
                    surface: { 50:'#f8fafc',100:'#f1f5f9',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a',950:'#020617' },
                },
            }},
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles

    <style>
        [x-cloak] { display: none !important; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-track { background: transparent; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(148,163,184,0.4); border-radius: 9999px; }
        .glass-card { background: rgba(255,255,255,0.85); backdrop-filter: blur(12px); border: 1px solid rgba(255,255,255,0.18); }
        .mesh-gradient {
            background-color: #f1f5f9;
            background-image:
                radial-gradient(at 40% 20%, rgba(245,158,11,0.08) 0px, transparent 50%),
                radial-gradient(at 80% 0%, rgba(245,158,11,0.05) 0px, transparent 50%),
                radial-gradient(at 0% 50%, rgba(180,83,9,0.05) 0px, transparent 50%);
        }
        .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 12px 24px -8px rgba(0,0,0,0.12); }
        main { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="h-full font-sans antialiased bg-surface-100">
    <div x-data="{ sidebarOpen: false }" class="min-h-full">
        <!-- Mobile overlay -->
        <div x-show="sidebarOpen" x-cloak class="relative z-50 lg:hidden" role="dialog" aria-modal="true">
            <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-linear duration-300"
                x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-surface-950/60 backdrop-blur-sm" @click="sidebarOpen = false"></div>
            <div class="fixed inset-0 flex">
                <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform"
                    x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
                    x-transition:leave="transition ease-in-out duration-300 transform"
                    x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
                    class="relative mr-16 flex w-full max-w-xs flex-1">
                    @include('portal.partials.sidebar')
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            @include('portal.partials.sidebar')
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Top navbar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 bg-white/80 backdrop-blur-lg border-b border-surface-200/60 px-4 sm:gap-x-6 sm:px-6 lg:px-8">
                <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-surface-500 hover:text-surface-700 lg:hidden">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex flex-1 items-center">
                        <h1 class="text-lg font-semibold text-surface-900">{{ $header ?? 'Sales Portal' }}</h1>
                    </div>
                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <div class="h-6 w-px bg-surface-200" aria-hidden="true"></div>
                        <div x-data="{ open: false }" class="relative">
                            <button @click="open = !open" class="-m-1.5 flex items-center p-1.5 group">
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 ring-2 ring-brand-200/50 shadow-lg shadow-brand-500/25">
                                    <span class="text-sm font-bold text-white">{{ substr(auth()->user()?->full_name ?? auth()->user()?->email ?? 'A', 0, 1) }}</span>
                                </span>
                                <span class="hidden lg:flex lg:items-center">
                                    <span class="ml-3 text-sm font-semibold text-surface-800">{{ auth()->user()?->full_name ?? auth()->user()?->email ?? 'Guest' }}</span>
                                    <svg class="ml-2 h-5 w-5 text-surface-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                    </svg>
                                </span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-xl bg-white py-2 shadow-xl ring-1 ring-surface-900/5">
                                <div class="px-4 py-3 border-b border-surface-100">
                                    <p class="text-xs text-surface-400">Signed in as</p>
                                    <p class="text-sm font-medium text-surface-800 truncate">{{ auth()->user()?->email ?? 'Guest' }}</p>
                                </div>
                                <a href="/portal" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-surface-700 hover:bg-surface-50">
                                    <i class="fas fa-home text-surface-400 w-4"></i> Back to SPA Portal
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2.5 text-sm text-surface-700 hover:bg-surface-50">
                                        <i class="fas fa-sign-out-alt text-surface-400 w-4"></i> Sign out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-8 mesh-gradient min-h-[calc(100vh-4rem)]">
                <div class="px-4 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                             class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200/60 p-4 shadow-sm">
                            <div class="flex items-center">
                                <div class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-emerald-100">
                                    <i class="fas fa-check text-emerald-600 text-sm"></i>
                                </div>
                                <div class="ml-3 flex-1"><p class="text-sm font-medium text-emerald-800">{{ session('success') }}</p></div>
                                <button @click="show = false" class="ml-auto text-emerald-400 hover:text-emerald-600"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    @endif
                    @if (session('error'))
                        <div x-data="{ show: true }" x-show="show" class="mb-6 rounded-xl bg-red-50 border border-red-200/60 p-4 shadow-sm">
                            <div class="flex items-center">
                                <div class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100">
                                    <i class="fas fa-exclamation text-red-600 text-sm"></i>
                                </div>
                                <div class="ml-3 flex-1"><p class="text-sm font-medium text-red-800">{{ session('error') }}</p></div>
                                <button @click="show = false" class="ml-auto text-red-400 hover:text-red-600"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    @endif
                    @if (session('warning'))
                        <div x-data="{ show: true }" x-show="show" class="mb-6 rounded-xl bg-amber-50 border border-amber-200/60 p-4 shadow-sm">
                            <div class="flex items-center">
                                <div class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-amber-100">
                                    <i class="fas fa-exclamation-triangle text-amber-600 text-sm"></i>
                                </div>
                                <div class="ml-3 flex-1"><p class="text-sm font-medium text-amber-800">{{ session('warning') }}</p></div>
                                <button @click="show = false" class="ml-auto text-amber-400 hover:text-amber-600"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                    @endif
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    <!-- Global Toast Notifications -->
    <div x-data="{ toasts: [], add(e) { const id = Date.now(); this.toasts.push({ id, text: e.detail.text, type: e.detail.type || 'info' }); setTimeout(() => { this.toasts = this.toasts.filter(t => t.id !== id) }, 4000); } }" 
         @notify.window="add($event)"
         class="fixed top-20 right-4 z-[9999] flex flex-col gap-2 pointer-events-none">
        <template x-for="toast in toasts" :key="toast.id">
            <div x-transition.duration.300ms 
                 class="px-5 py-3 rounded-xl shadow-xl border text-sm font-bold flex items-center gap-3 w-80 pointer-events-auto"
                 :class="{
                     'bg-red-950/90 text-red-300 border-red-800': toast.type === 'error',
                     'bg-emerald-950/90 text-emerald-300 border-emerald-800': toast.type === 'success',
                     'bg-blue-950/90 text-blue-300 border-blue-800': toast.type === 'info' || !toast.type
                 }">
                <i class="fas" :class="{
                    'fa-exclamation-circle text-red-500': toast.type === 'error',
                    'fa-check-circle text-emerald-500': toast.type === 'success',
                    'fa-info-circle text-blue-500': toast.type === 'info' || !toast.type
                }"></i>
                <span x-text="toast.text"></span>
            </div>
        </template>
    </div>

    <script>
        window.notify = function(text, type = 'error') {
            type = (type || 'error').toLowerCase();
            if(type === 'error' && arguments.length === 3) type = arguments[2].toLowerCase(); // Fallback if they pass title as second arg
            window.dispatchEvent(new CustomEvent('notify', { detail: { text, type } }));
        };
    </script>
    
    @livewireScripts
    @stack('scripts')
</body>
</html>
