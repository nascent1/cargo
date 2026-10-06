<x-layouts.admin>
    <x-slot:title>Dual Control Settings</x-slot:title>
    <x-slot:header>Dual Control Configuration</x-slot:header>

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-800">Operation Policies</h3>
            <p class="text-sm text-slate-500">Toggle which operations require a secondary authorization (maker/checker).</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase tracking-wider text-slate-500">
                        <th class="p-4 font-bold">Operation Type</th>
                        <th class="p-4 font-bold">Description</th>
                        <th class="p-4 font-bold">Status</th>
                        <th class="p-4 font-bold text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $groupedConfigs = $configs->groupBy('module');
                    @endphp

                    @forelse($groupedConfigs as $module => $moduleConfigs)
                        <tr class="bg-slate-100 border-t border-b border-slate-200">
                            <td colspan="4" class="p-3 px-4 font-bold text-slate-700 uppercase tracking-wider text-xs">
                                <i class="fas fa-layer-group mr-2 text-slate-400"></i> {{ $module }}
                            </td>
                        </tr>
                        @foreach($moduleConfigs as $config)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="p-4">
                                    <div class="font-semibold text-slate-800">{{ Str::headline($config->operation_type) }}</div>
                                    <div class="text-xs text-slate-500 font-mono mt-1">{{ $config->operation_type }}</div>
                                </td>
                                <td class="p-4 text-sm text-slate-600">
                                    {{ $config->description }}
                                </td>
                                <td class="p-4">
                                    @if($config->requires_dual_control)
                                        <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-md text-xs font-bold"><i class="fas fa-lock mr-1"></i> Enabled</span>
                                    @else
                                        <span class="px-2 py-1 bg-slate-100 text-slate-600 rounded-md text-xs font-bold"><i class="fas fa-unlock mr-1"></i> Disabled</span>
                                    @endif
                                </td>
                                <td class="p-4 text-right">
                                    <form action="{{ route('admin.settings.dual-control-configs.toggle', $config) }}" method="POST">
                                        @csrf
                                        @php $canUpdateSettings = auth()->user()->hasPermission('setting:update'); @endphp
                                        @if($config->requires_dual_control)
                                            @if($canUpdateSettings)
                                            <button type="submit" class="px-3 py-1.5 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg text-sm font-medium transition-colors">
                                                Disable
                                            </button>
                                            @else
                                            <button type="button" disabled class="px-3 py-1.5 bg-rose-50/50 text-rose-300 rounded-lg text-sm font-medium cursor-not-allowed" title="Disable (No Permission)">
                                                Disable
                                            </button>
                                            @endif
                                        @else
                                            @if($canUpdateSettings)
                                            <button type="submit" class="px-3 py-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg text-sm font-medium transition-colors">
                                                Enable
                                            </button>
                                            @else
                                            <button type="button" disabled class="px-3 py-1.5 bg-emerald-50/50 text-emerald-300 rounded-lg text-sm font-medium cursor-not-allowed" title="Enable (No Permission)">
                                                Enable
                                            </button>
                                            @endif
                                        @endif
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 text-center text-slate-500">
                                <i class="fas fa-shield-alt text-4xl mb-3 text-slate-300"></i>
                                <p>No configurations found in the database.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.admin>
