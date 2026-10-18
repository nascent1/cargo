{{--
    Public cargo tracking (no login), opened from booking SMS and receipt QR codes.
    @param string|null $number  what was looked up
    @param array|null  $result  PublicTrackingService::lookup() — public-safe fields only
    @param string      $company
--}}
@php
    $logo = \App\Models\SystemConfig::getValue('company_logo_url', '/images/logo.png');
    $tone = [
        'pending_payment' => 'bg-orange-50 text-orange-800 border-orange-200',
        'cancelled' => 'bg-red-50 text-red-800 border-red-200',
        'unclaimed' => 'bg-rose-50 text-rose-800 border-rose-200',
        'collected' => 'bg-teal-50 text-teal-800 border-teal-200',
        'delivered' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    ][$result['status'] ?? ''] ?? 'bg-blue-50 text-blue-800 border-blue-200';
    $date = fn (?string $iso, string $format = 'D d M, H:i') => $iso ? \Carbon\Carbon::parse($iso)->format($format) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $result ? "Booking {$result['number']}" : 'Track your cargo' }} — {{ $company }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 antialiased">
    <header class="bg-white border-b border-slate-200">
        <div class="max-w-xl mx-auto px-4 py-3 flex items-center gap-3">
            <img src="{{ $logo }}" alt="" class="h-9 w-9 rounded-lg object-cover">
            <div class="leading-tight">
                <p class="font-bold text-slate-900">{{ $company }}</p>
                <p class="text-xs text-slate-500">Cargo tracking</p>
            </div>
        </div>
    </header>

    <main class="max-w-xl mx-auto px-4 py-5 space-y-4">
        <form method="GET" action="{{ route('tracking.search') }}" class="flex gap-2" role="search">
            <label for="number" class="sr-only">Booking or tracking number</label>
            <input id="number" name="number" value="{{ $result['number'] ?? $number }}" required maxlength="40" autocomplete="off"
                   placeholder="Booking number, e.g. CG-261014-CLA9G"
                   class="flex-1 min-w-0 rounded-xl border border-slate-300 bg-white px-4 py-3 text-base uppercase placeholder:normal-case focus:outline-none focus:ring-2 focus:ring-blue-500">
            <button class="shrink-0 rounded-xl bg-blue-600 px-4 py-3 font-semibold text-white hover:bg-blue-700"><i class="fas fa-magnifying-glass"></i><span class="sr-only">Track</span></button>
        </form>

        @if($number !== null && !$result)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="alert">
                <p class="font-semibold"><i class="fas fa-circle-question mr-1"></i>We couldn't find “{{ \Illuminate\Support\Str::limit($number, 40) }}”.</p>
                <p class="mt-1">Check the booking number on your SMS or receipt and try again.</p>
            </div>
        @elseif(!$result)
            <p class="text-sm text-slate-600">Enter the booking number from your SMS or receipt to see where your cargo is.</p>
        @else
            {{-- Status --}}
            <section class="rounded-2xl bg-white border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-4 sm:p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Booking</p>
                            <p class="font-mono text-lg font-bold text-slate-900">{{ $result['number'] }}</p>
                        </div>
                        <span class="rounded-full border px-3 py-1 text-xs font-bold {{ $tone }}">{{ $result['status_label'] }}</span>
                    </div>
                    <p class="mt-3 text-base text-slate-800">{{ $result['message'] }}</p>
                </div>

                <dl class="grid grid-cols-2 gap-px bg-slate-100 border-t border-slate-100 text-sm">
                    <div class="bg-white p-4 col-span-2">
                        <dt class="text-xs text-slate-500">Route</dt>
                        <dd class="font-semibold text-slate-900">{{ $result['route']['from'] ?? '—' }} <i class="fas fa-arrow-right text-xs text-slate-400 mx-1"></i> {{ $result['route']['to'] ?? '—' }}</dd>
                    </div>
                    <div class="bg-white p-4">
                        <dt class="text-xs text-slate-500">Departure</dt>
                        <dd class="font-semibold text-slate-900">{{ $date($result['departure']['at'] ?? null) ?? '—' }}</dd>
                    </div>
                    <div class="bg-white p-4">
                        <dt class="text-xs text-slate-500">Vessel</dt>
                        <dd class="font-semibold text-slate-900">{{ $result['departure']['vessel'] ?? '—' }}</dd>
                    </div>
                    <div class="bg-white p-4">
                        <dt class="text-xs text-slate-500">Items</dt>
                        <dd class="font-semibold text-slate-900">{{ $result['items'] }}</dd>
                    </div>
                    <div class="bg-white p-4">
                        <dt class="text-xs text-slate-500">{{ $result['estimated_arrival'] ? 'Expected arrival' : 'Sender → receiver' }}</dt>
                        <dd class="font-semibold text-slate-900">{{ $result['estimated_arrival'] ? $date($result['estimated_arrival']) : $result['sender'] . ' → ' . $result['receiver'] }}</dd>
                    </div>
                    @if($result['estimated_arrival'])
                        <div class="bg-white p-4 col-span-2">
                            <dt class="text-xs text-slate-500">Sender → receiver</dt>
                            <dd class="font-semibold text-slate-900">{{ $result['sender'] }} → {{ $result['receiver'] }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- Timeline --}}
            @if($result['timeline'])
                <section class="rounded-2xl bg-white border border-slate-200 shadow-sm p-4 sm:p-5">
                    <h2 class="text-sm font-bold text-slate-900 mb-3">Progress</h2>
                    <ol class="relative">
                        @foreach($result['timeline'] as $step)
                            <li class="relative flex gap-3 pb-5 last:pb-0">
                                @unless($loop->last)
                                    <span class="absolute left-[11px] top-6 bottom-0 w-0.5 {{ $step['reached'] && $result['timeline'][$loop->index + 1]['reached'] ? 'bg-blue-500' : 'bg-slate-200' }}" aria-hidden="true"></span>
                                @endunless
                                <span class="relative z-10 mt-0.5 h-6 w-6 shrink-0 rounded-full flex items-center justify-center text-[11px]
                                    {{ $step['current'] ? 'bg-blue-600 text-white ring-4 ring-blue-100' : ($step['reached'] ? 'bg-blue-500 text-white' : 'bg-white border-2 border-slate-300 text-transparent') }}">
                                    <i class="fas fa-check"></i>
                                </span>
                                <div class="min-w-0">
                                    <p class="text-sm {{ $step['reached'] ? 'font-semibold text-slate-900' : 'text-slate-400' }}">{{ $step['label'] }}</p>
                                    @if($step['at'])<p class="text-xs text-slate-500">{{ $date($step['at']) }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            <p class="text-center text-xs text-slate-400">Last updated {{ $date($result['updated_at'], 'd M Y, H:i') }} · names are shortened for privacy</p>
        @endif
    </main>
</body>
</html>
