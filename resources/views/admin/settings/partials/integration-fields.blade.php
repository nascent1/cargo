{{--
    Provider fields for one integration form.
    @param array      $meta        catalog entry (IntegrationCatalog::PROVIDERS[...])
    @param array      $values      non-secret field values keyed by field
    @param array      $secretsSet  field => bool, whether a secret is stored
    @param ?\App\Models\IntegrationConfig $config  null when adding
    @param bool       $editable
    @param string     $input       input CSS classes
--}}
@php
    $env = old('environment', $config->environment ?? 'production');
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
    <label class="block text-xs font-bold text-slate-600 sm:col-span-2">Display name <span class="text-rose-500">*</span>
        <input type="text" name="name" value="{{ old('name', $config->name ?? $meta['label']) }}" maxlength="100" required class="{{ $input }} mt-1" @disabled(!$editable)>
    </label>

    @if($meta['uses_urls'])
        <label class="block text-xs font-bold text-slate-600">Environment
            <select name="environment" class="{{ $input }} mt-1" @disabled(!$editable)>
                <option value="production" @selected($env === 'production')>Production</option>
                <option value="sandbox" @selected($env === 'sandbox')>Sandbox</option>
            </select>
        </label>
        <div class="hidden sm:block"></div>
        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">Production URL
            <input type="url" name="production_url" value="{{ old('production_url', $config->production_url ?? ($meta['urls']['production'] ?? '')) }}" class="{{ $input }} mt-1 font-mono text-xs" @disabled(!$editable)>
        </label>
        <label class="block text-xs font-bold text-slate-600 sm:col-span-2">Sandbox URL
            <input type="url" name="sandbox_url" value="{{ old('sandbox_url', $config->sandbox_url ?? ($meta['urls']['sandbox'] ?? '')) }}" class="{{ $input }} mt-1 font-mono text-xs" @disabled(!$editable)>
        </label>
    @endif

    @foreach($meta['fields'] as $field => $f)
        @php
            $name = \App\Support\IntegrationCatalog::inputName($field);
            $type = $f['type'] ?? 'text';
            $isSecret = $f['secret'] ?? false;
            $value = old($name, $values[$field] ?? ($config ? null : ($f['default'] ?? null)));
        @endphp
        <div>
            <label class="block text-xs font-bold text-slate-600" for="{{ $name }}_{{ $config->id ?? 'new' }}">
                {{ $f['label'] }} @if($f['required'] ?? false)<span class="text-rose-500">*</span>@endif
            </label>
            @if($isSecret)
                <input type="password" id="{{ $name }}_{{ $config->id ?? 'new' }}" name="{{ $name }}" autocomplete="new-password"
                       placeholder="{{ ($secretsSet[$field] ?? false) ? '•••••••• saved' : 'Not set' }}"
                       title="{{ ($secretsSet[$field] ?? false) ? 'A value is saved. Leave blank to keep it, or type a new one to replace it.' : '' }}"
                       class="{{ $input }} mt-1 font-mono" @disabled(!$editable)>
                @if(($secretsSet[$field] ?? false) && $editable)
                    <label class="mt-1 inline-flex items-center gap-1 text-[11px] text-slate-500">
                        <input type="checkbox" name="clear[]" value="{{ str_replace('.', '__', $field) }}" class="rounded border-slate-300"> Clear stored value
                    </label>
                @endif
            @elseif($type === 'select')
                <select id="{{ $name }}_{{ $config->id ?? 'new' }}" name="{{ $name }}" class="{{ $input }} mt-1" @disabled(!$editable)>
                    @foreach($f['options'] as $optValue => $optLabel)
                        <option value="{{ $optValue }}" @selected((string) $value === (string) $optValue)>{{ $optLabel }}</option>
                    @endforeach
                </select>
            @else
                <input type="{{ in_array($type, ['number', 'email'], true) ? $type : 'text' }}" id="{{ $name }}_{{ $config->id ?? 'new' }}" name="{{ $name }}"
                       value="{{ $value }}" placeholder="{{ $f['placeholder'] ?? '' }}" @if(isset($f['max'])) maxlength="{{ $f['max'] }}" @endif
                       class="{{ $input }} mt-1" @disabled(!$editable)>
            @endif
        </div>
    @endforeach

</div>
