{{--
    "Takes effect: Immediately / On [date time]" for a change that goes through dual control,
    plus whether a checker must approve it. Posts takes_effect (now|at) and takes_effect_at.

    <x-dual-control.takes-effect op="station_update" />
    <x-dual-control.takes-effect op="schedule_update" wire />   (Livewire: binds takesEffect / takesEffectAt)
    <x-dual-control.takes-effect op="exchange_rate_create" :schedule="false" />   (record has its own start date: approval note only)
--}}
@props(['op', 'wire' => false, 'label' => 'Takes effect', 'schedule' => true])
@php
    $operation = \App\Services\DualControl\DualControlRegistry::get($op);
    $needsApproval = app(\App\Services\DualControlService::class)->requiresDualControl($op);
    $canSchedule = $schedule && in_array($operation['timing'], ['scheduled', 'dated'], true);
    $mode = old('takes_effect', 'now');
    $min = now()->addMinutes(5)->format('Y-m-d\TH:i');
@endphp

@if ($canSchedule || $needsApproval)
<div {{ $attributes->merge(['class' => 'rounded-xl border px-4 py-3 text-sm ' . ($needsApproval ? 'border-amber-200 bg-amber-50/60' : 'border-slate-200 bg-slate-50')]) }}
    @if ($canSchedule) x-data="{ mode: {{ $wire ? "\$wire.entangle('takesEffect')" : \Illuminate\Support\Js::from($mode) }} }" @endif>
    @if ($canSchedule)
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2" role="radiogroup" aria-label="{{ $label }}">
            <span class="text-xs font-semibold text-slate-700">{{ $label }}</span>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700">
                <input type="radio" name="takes_effect" value="now" x-model="mode" class="text-brand-600 focus:ring-brand-500"> Immediately
            </label>
            <label class="inline-flex items-center gap-1.5 cursor-pointer text-slate-700">
                <input type="radio" name="takes_effect" value="at" x-model="mode" class="text-brand-600 focus:ring-brand-500"> On
            </label>
            <input type="datetime-local" name="takes_effect_at" value="{{ old('takes_effect_at') }}" min="{{ $min }}"
                x-show="mode === 'at'" x-cloak :required="mode === 'at'" aria-label="Date and time it takes effect"
                @if ($wire) wire:model="takesEffectAt" @endif
                class="rounded-lg border-slate-300 py-1.5 px-2 text-sm focus:border-brand-500 focus:ring-brand-500">
        </div>
    @endif
    <p class="text-xs {{ $canSchedule ? 'mt-1.5' : '' }} {{ $needsApproval ? 'text-amber-800' : 'text-slate-500' }}">
        @if ($needsApproval)
            <i class="fas fa-user-shield mr-1"></i>Needs a checker's approval.
            @if ($canSchedule) If it is approved after the chosen start, it applies when approved. @endif
        @else
            <i class="fas fa-bolt mr-1"></i>Applies without approval{{ $canSchedule ? ', immediately or at the chosen start.' : '.' }}
        @endif
    </p>
    @error('takes_effect_at') <p class="text-xs text-red-700 mt-1">{{ $message }}</p> @enderror
</div>
@endif
