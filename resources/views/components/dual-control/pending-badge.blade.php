{{--
    Badge for a record with an open dual-control change: awaiting approval, or scheduled for a moment.
    <x-dual-control.pending-badge subject="station:{{ $station->id }}" />
--}}
@props(['subject'])
@php $open = \App\Models\DualControlRequest::openFor($subject); @endphp

@if ($open)
    @php $scheduled = $open->status === 'scheduled'; @endphp
    <a href="{{ route('admin.dual-control.show', $open) }}"
        title="{{ $open->label() }} — #DC-{{ $open->id }}{{ $open->commence_at ? ', takes effect ' . $open->commence_at->format('d M Y H:i') : '' }}"
        {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border whitespace-nowrap ' . ($scheduled ? 'bg-sky-50 text-sky-800 border-sky-200' : 'bg-amber-50 text-amber-800 border-amber-200')]) }}>
        <i class="fas {{ $scheduled ? 'fa-clock' : 'fa-hourglass-half' }}"></i>
        {{ $scheduled ? 'Changes ' . $open->commence_at->format('d M H:i') : 'Change awaiting approval' }}
    </a>
@endif
