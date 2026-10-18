{{-- One report cell. Expects $value, $type, $row; $pdf = true for plain output. --}}
@php
    $pdf = $pdf ?? false;
    $statusLabels = [
        'pending_payment' => 'Awaiting payment', 'booked' => 'Booked', 'loaded' => 'Loaded', 'in_transit' => 'In transit',
        'delivered' => 'Delivered', 'unclaimed' => 'Unclaimed', 'collected' => 'Collected', 'cancelled' => 'Cancelled',
        'paid' => 'Paid', 'unpaid' => 'Unpaid', 'awaiting_customer' => 'Awaiting customer', 'initiated' => 'Started',
        'sending' => 'Sending', 'failed' => 'Failed', 'expired' => 'Expired', 'refund_due' => 'Refund due',
    ];
    $tone = [
        'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'collected' => 'bg-teal-50 text-teal-700 border-teal-200',
        'delivered' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'cancelled' => 'bg-red-50 text-red-700 border-red-200',
        'failed' => 'bg-red-50 text-red-700 border-red-200', 'refund_due' => 'bg-amber-50 text-amber-800 border-amber-300',
        'pending_payment' => 'bg-orange-50 text-orange-700 border-orange-200', 'unclaimed' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
@endphp
@if($value === null || $value === '')
    <span class="{{ $pdf ? '' : 'text-surface-400' }}">—</span>
@else
    @switch($type)
        @case('int')
            {{ number_format((float) $value) }}
            @break
        @case('num')
        @case('kg')
            {{ number_format((float) $value, (float) $value == floor((float) $value) ? 0 : 1) }}{{ $type === 'kg' ? ' kg' : '' }}
            @break
        @case('money')
            {{ number_format((float) $value) }}
            @break
        @case('pct')
            {{ number_format((float) $value, 1) }}%
            @break
        @case('datetime')
            {{ \Carbon\Carbon::parse($value)->format('d M Y H:i') }}
            @break
        @case('date')
            {{ \Carbon\Carbon::parse($value)->format('d M Y') }}
            @break
        @case('status')
        @case('payment')
        @case('attempt')
            @if($pdf)
                {{ $statusLabels[$value] ?? \Illuminate\Support\Str::headline($value) }}
            @else
                <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap {{ $tone[$value] ?? 'bg-surface-100 text-surface-700 border-surface-300' }}">{{ $statusLabels[$value] ?? \Illuminate\Support\Str::headline($value) }}</span>
            @endif
            @break
        @case('booking')
            @if(!$pdf && !empty($row['id']))
                <a href="{{ route('admin.cargo.bookings.show', $row['id']) }}" class="font-mono text-xs font-bold text-brand-700 hover:underline">{{ $value }}</a>
            @else
                {{ $value }}
            @endif
            @break
        @default
            {{ $value }}
    @endswitch
@endif
