<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Dashboard' }} — Ferry Booking Platform</title>
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
                    secondary: { 50:'#ffffff',100:'#fdfdfd',200:'#f8f9fa',300:'#f1f3f5',400:'#e9ecef',500:'#dee2e6',600:'#ced4da',700:'#adb5bd',800:'#6c757d',900:'#495057',950:'#212529' },
                    surface: { 50:'#ffffff',100:'#f8f9fa',200:'#e9ecef',300:'#dee2e6',400:'#ced4da',500:'#adb5bd',600:'#6c757d',700:'#495057',800:'#343a40',900:'#212529',950:'#121416' },
                },
            }},
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @livewireStyles
    @stack('styles')

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
<body class="h-full font-sans antialiased bg-surface-100 selection:bg-brand-500 selection:text-white">
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
                    class="relative mr-16 flex w-full max-w-xs flex-1" @click.away="sidebarOpen = false">
                    @include('admin.partials.sidebar')
                </div>
            </div>
        </div>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-72 lg:flex-col">
            @include('admin.partials.sidebar')
        </div>

        <!-- Main content -->
        <div class="lg:pl-72">
            <!-- Top navbar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 bg-brand-600 shadow-md border-b border-brand-700 px-4 sm:gap-x-6 sm:px-6 lg:px-8 text-white">
                <button type="button" @click="sidebarOpen = true" class="-m-2.5 p-2.5 text-brand-100 hover:text-white lg:hidden">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <div class="flex flex-1 items-center">
                        <h1 class="text-lg font-bold text-white">{{ $header ?? 'Dashboard' }}</h1>
                    </div>
                    <!-- Notifications Bell -->
                    <div x-data="{
                        open: false,
                        list: [],
                        unreadCount: 0,
                        async load() {
                            const res = await fetch('{{ route('api.notifications') }}');
                            if (res.ok) {
                                this.list = await res.json();
                                this.unreadCount = this.list.length;
                            }
                        },
                        async markAsRead(id) {
                            const res = await fetch(`/api/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Content-Type': 'application/json'
                                }
                            });
                            if (res.ok) {
                                this.list = this.list.filter(n => n.id !== id);
                                this.unreadCount = this.list.length;
                            }
                        },
                        init() {
                            this.load();
                            // Poll every 30 seconds
                            setInterval(() => this.load(), 30000);
                        }
                    }" class="relative">
                        <button type="button" @click="open = !open" class="relative rounded-full p-2 text-brand-100 hover:text-white hover:bg-brand-700 transition-colors focus:outline-none">
                            <span class="sr-only">View notifications</span>
                            <i class="fas fa-bell text-xl"></i>
                            <template x-if="unreadCount > 0">
                                <span class="absolute top-1 right-1 flex h-3 w-3">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500 border-2 border-brand-600"></span>
                                </span>
                            </template>
                        </button>
                        <div x-show="open" @click.away="open = false" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="transform opacity-0 scale-95"
                            x-transition:enter-end="transform opacity-100 scale-100"
                            class="absolute right-0 z-10 mt-2.5 w-80 origin-top-right rounded-xl bg-white py-2 shadow-xl ring-1 ring-surface-900/5 divide-y divide-surface-100">
                            <div class="px-4 py-2 flex items-center justify-between">
                                <span class="text-sm font-semibold text-surface-800">Notifications</span>
                                <span x-text="unreadCount" class="inline-flex items-center rounded-md bg-brand-50 px-2 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-700/10">0</span>
                            </div>
                            <div class="max-h-60 overflow-y-auto py-1">
                                <template x-if="list.length === 0">
                                    <div class="px-4 py-6 text-center text-sm text-surface-400">
                                        No new notifications
                                    </div>
                                </template>
                                <template x-for="item in list" :key="item.id">
                                    <div class="px-4 py-3 hover:bg-surface-50 flex items-start justify-between gap-3">
                                        <div class="flex-1 min-w-0">
                                            <p x-text="item.title" class="text-xs font-semibold text-surface-900"></p>
                                            <p x-text="item.message" class="text-xs text-surface-500 mt-0.5 whitespace-normal break-words"></p>
                                            <p x-text="new Date(item.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})" class="text-[10px] text-surface-400 mt-1"></p>
                                        </div>
                                        <button @click="markAsRead(item.id)" class="text-surface-400 hover:text-brand-600">
                                            <i class="fas fa-check text-xs"></i>
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-x-4 lg:gap-x-6">
                        <!-- Separator -->
                        <div class="hidden lg:block lg:h-6 lg:w-px lg:bg-brand-400/50" aria-hidden="true"></div>

                        <!-- Profile dropdown -->
                        <div class="relative" x-data="{ open: false }" @click.away="open = false">
                            <button type="button" @click="open = !open" class="-m-1.5 flex items-center p-1.5 hover:bg-brand-700 rounded-lg transition-colors" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                <span class="sr-only">Open user menu</span>
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-white text-brand-700 ring-2 ring-brand-300 shadow-lg font-bold">
                                    {{ substr(auth()->user()->first_name ?? 'A', 0, 1) }}{{ substr(auth()->user()->last_name ?? 'U', 0, 1) }}
                                </span>
                                <span class="hidden lg:flex lg:items-center">
                                    <span class="ml-4 text-sm font-semibold leading-6 text-white" aria-hidden="true">
                                        {{ auth()->user()->first_name ?? 'Guest' }} {{ auth()->user()->last_name ?? '' }}
                                    </span>
                                    <i class="fas fa-chevron-down ml-2 text-xs text-brand-200"></i>
                                </span>
                            </button>
                            <div x-show="open" @click.away="open = false" x-cloak
                                x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
                                class="absolute right-0 z-10 mt-2.5 w-48 origin-top-right rounded-xl bg-white py-2 shadow-xl ring-1 ring-surface-900/5">
                                <div class="px-4 py-3 border-b border-surface-100">
                                    <p class="text-xs text-surface-400">Signed in as</p>
                                    <p class="text-sm font-medium text-surface-800 truncate">{{ auth()->user()?->email ?? 'Guest' }}</p>
                                </div>
                                <div class="py-1">
                                    <a href="{{ route('admin.settings.change-password') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-surface-700 hover:bg-surface-50">
                                        <i class="fas fa-key text-surface-400 w-4"></i> Change Password
                                    </a>
                                    <a href="{{ route('admin.2fa.setup') }}" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-surface-700 hover:bg-surface-50">
                                        <i class="fas fa-shield-alt text-surface-400 w-4"></i> 2FA Security
                                    </a>
                                </div>
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
                    {{ $slot }}
                </div>
            </main>
        </div>
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

    @if(session('force_exchange_modal'))
        <div class="fixed inset-0 z-[99999] flex items-center justify-center bg-surface-900/80 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-[fadeIn_0.3s_ease-out]">
                <div class="bg-amber-50 px-6 py-4 border-b border-amber-100 flex items-center gap-3">
                    <div class="h-10 w-10 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 shrink-0">
                        <i class="fas fa-exclamation-triangle text-lg"></i>
                    </div>
                    <div>
                        @php
                            $freqDays = (int) \App\Models\SystemConfig::getValue('exchange_rate_update_frequency', 1);
                            $freqLabel = $freqDays === 1 ? 'Daily' : "Every {$freqDays} Days";
                        @endphp
                        <h3 class="font-bold text-amber-900">{{ $freqLabel }} Exchange Rate Required</h3>
                        <p class="text-xs text-amber-700 mt-0.5">Please update the USD to TZS exchange rate.</p>
                    </div>
                </div>
                <form action="{{ route('admin.settings.update') }}" method="POST" class="p-6">
                    @csrf
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-surface-700 mb-1">Current USD to TZS Rate</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-surface-400 font-semibold">TZS</span>
                            <input type="number" step="0.01" name="settings[usd_to_tzs_exchange_rate]" value="{{ \App\Models\SystemConfig::getValue('usd_to_tzs_exchange_rate', '2600') }}" required class="block w-full rounded-lg border-surface-300 pl-12 pr-3 py-2.5 text-sm focus:border-brand-500 focus:ring-brand-500 shadow-sm" />
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-brand-600 hover:bg-brand-700 text-white font-bold py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                        <i class="fas fa-save"></i> Save Exchange Rate
                    </button>
                </form>
            </div>
        </div>
    @endif

    @stack('modals')
</body>
</html>
