<x-layouts.admin>
    <x-slot:title>Lookups & Statuses</x-slot:title>
    <x-slot:header>System Settings</x-slot:header>

    @include('admin.settings._tabs')

    <div class="mb-8" x-data="{ openAddModal: false, editingItem: null }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-lg font-bold text-surface-900 flex items-center gap-2">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-brand-50 text-brand-600">
                        <i class="fas fa-list-check text-sm"></i>
                    </span>
                    {{ $config['title'] }}
                </h3>
                <p class="text-sm text-surface-500 mt-1">{{ $config['description'] }}</p>
            </div>
            @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
            @if($canUpdateSetting)
            <button @click="openAddModal = true; editingItem = null" class="inline-flex items-center gap-2 bg-brand-500 hover:bg-brand-600 text-white px-4 py-2 rounded-xl font-medium shadow-sm transition-all text-sm">
                <i class="fas fa-plus"></i> Add Lookup Item
            </button>
            @else
            <button disabled class="inline-flex items-center gap-2 bg-slate-100 border border-slate-200 text-slate-400 px-4 py-2 rounded-xl font-medium shadow-sm cursor-not-allowed text-sm" title="Add Lookup Item (No Permission)">
                <i class="fas fa-plus"></i> Add Lookup Item
            </button>
            @endif
        </div>

        {{-- Lookup Table Switcher Sub-tabs --}}
        <div class="flex flex-wrap gap-2 mb-6 border-b border-surface-200 pb-4">
            @foreach($allowedTables as $tKey => $tConfig)
                <a href="{{ route('admin.settings.lookups.index', ['tab' => $tKey]) }}"
                   class="px-3.5 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1.5 {{ $activeTab === $tKey ? 'bg-surface-900 text-white shadow-sm' : 'bg-white text-surface-600 hover:bg-surface-100 border border-surface-200' }}">
                    <span>{{ $tConfig['title'] }}</span>
                </a>
            @endforeach
        </div>

        {{-- Table Content --}}
        <div class="glass-card rounded-2xl overflow-hidden shadow-sm border border-surface-200/60">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-surface-50/80 border-b border-surface-200/80 text-xs uppercase font-bold text-surface-500 tracking-wider">
                            <th class="py-3.5 px-6">ID</th>
                            <th class="py-3.5 px-6">System Code</th>
                            <th class="py-3.5 px-6">Display Label</th>
                            @if(in_array('color_badge', $config['fields']) || in_array('color', $config['fields']))
                                <th class="py-3.5 px-6">Badge Preview</th>
                            @endif
                            @if(in_array('direction', $config['fields']))
                                <th class="py-3.5 px-6">Direction</th>
                            @endif
                            @if(in_array('sort_order', $config['fields']))
                                <th class="py-3.5 px-6">Sort Order</th>
                            @endif
                            @if(in_array('is_terminal', $config['fields']))
                                <th class="py-3.5 px-6">Terminal Stage</th>
                            @endif
                            <th class="py-3.5 px-6 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-100 text-sm">
                        @forelse($items as $item)
                            <tr class="hover:bg-surface-50/50 transition-colors">
                                <td class="py-4 px-6 font-mono text-xs text-surface-400">#{{ $item->id }}</td>
                                <td class="py-4 px-6 font-mono text-xs font-semibold text-brand-700 bg-brand-50/40 rounded px-2 py-1 w-max">{{ $item->code ?? $item->slug ?? '-' }}</td>
                                <td class="py-4 px-6 font-bold text-surface-900">{{ $item->label ?? $item->name ?? '-' }}</td>
                                @if(in_array('color_badge', $config['fields']) || in_array('color', $config['fields']))
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $item->color_badge ?? $item->color ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $item->label ?? $item->name ?? '-' }}
                                        </span>
                                    </td>
                                @endif
                                @if(in_array('direction', $config['fields']))
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase {{ $item->direction === 'in' ? 'bg-emerald-100 text-emerald-800' : ($item->direction === 'out' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800') }}">
                                            {{ $item->direction }}
                                        </span>
                                    </td>
                                @endif
                                @if(in_array('sort_order', $config['fields']))
                                    <td class="py-4 px-6 text-surface-600 font-medium">{{ $item->sort_order }}</td>
                                @endif
                                @if(in_array('is_terminal', $config['fields']))
                                    <td class="py-4 px-6">
                                        @if($item->is_terminal)
                                            <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded">
                                                <i class="fas fa-check-circle text-xs"></i> Yes
                                            </span>
                                        @else
                                            <span class="text-xs text-surface-400">No</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="py-4 px-6 text-right space-x-2">
                                    @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
                                    @if($canUpdateSetting)
                                    <button @click="editingItem = {{ json_encode($item) }}; openAddModal = true"
                                            class="text-surface-500 hover:text-brand-600 transition-colors p-1" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    @php $approveDelete ??= app(\App\Services\DualControlService::class)->requiresDualControl('lookup_delete'); @endphp
                                    <form action="{{ route('admin.settings.lookups.destroy', ['table' => $activeTab, 'id' => $item->id]) }}" method="POST" class="inline"
                                        data-confirm="{{ $approveDelete ? 'A checker must approve it first.' : '' }}"
                                        data-confirm-title="Delete “{{ data_get($item, 'label') ?? data_get($item, 'name') ?? data_get($item, 'code') ?? 'this item' }}”?"
                                        data-confirm-button="{{ $approveDelete ? 'Request deletion' : 'Delete' }}" data-confirm-tone="danger">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-surface-400 hover:text-rose-600 transition-colors p-1" title="Delete">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                    @else
                                    <button disabled class="text-surface-300 cursor-not-allowed p-1" title="Edit (No Permission)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button disabled class="text-rose-200 cursor-not-allowed p-1" title="Delete (No Permission)">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-8 text-center text-surface-400 text-sm">
                                    No entries found in {{ $config['title'] }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Add / Edit Modal --}}
        <div x-show="openAddModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="openAddModal" @click="openAddModal = false" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-surface-900/60 backdrop-blur-sm transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div x-show="openAddModal" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-surface-200">
                    <form method="POST" action="{{ route('admin.settings.lookups.update-or-create', ['table' => $activeTab]) }}">
                        @csrf
                        <input type="hidden" name="id" :value="editingItem ? editingItem.id : ''">
                        
                        <div class="px-6 pt-6 pb-4">
                            <h3 class="text-lg font-bold text-surface-900" x-text="editingItem ? 'Edit Lookup Item' : 'Add New Lookup Item'"></h3>
                            <p class="text-xs text-surface-500 mt-0.5">Define status code and display label for {{ $config['title'] }}.</p>
                        </div>

                        <div class="px-6 py-4 space-y-4 border-t border-b border-surface-100 bg-surface-50/30">
                            <div>
                                <label class="block text-xs font-bold uppercase text-surface-700 mb-1">System Code (slug/identifier) *</label>
                                <input type="text" name="code" :value="editingItem ? (editingItem.code || editingItem.slug || '') : ''" required placeholder="e.g. processing_quality" class="w-full rounded-xl border border-surface-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-brand-500 font-mono">
                                <p class="text-[11px] text-surface-400 mt-1">Unique snake_case identifier used internally by the system.</p>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-surface-700 mb-1">Display Label *</label>
                                <input type="text" name="label" :value="editingItem ? (editingItem.label || editingItem.name || '') : ''" required placeholder="e.g. Processing Quality" class="w-full rounded-xl border border-surface-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-brand-500">
                            </div>

                            @if(in_array('color_badge', $config['fields']) || in_array('color', $config['fields']))
                            <div>
                                <label class="block text-xs font-bold uppercase text-surface-700 mb-1">Tailwind Badge Classes</label>
                                <input type="text" name="color_badge" :value="editingItem ? (editingItem.color_badge || editingItem.color || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800'" placeholder="e.g. bg-blue-100 text-blue-800" class="w-full rounded-xl border border-surface-300 px-3.5 py-2 text-sm font-mono text-xs focus:ring-2 focus:ring-brand-500">
                            </div>
                            @endif

                            @if(in_array('direction', $config['fields']))
                            <div>
                                <label class="block text-xs font-bold uppercase text-surface-700 mb-1">Movement Direction *</label>
                                <select name="direction" class="w-full rounded-xl border border-surface-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-brand-500">
                                    <option value="in" :selected="editingItem && editingItem.direction === 'in'">Inbound (Stock Increase)</option>
                                    <option value="out" :selected="editingItem && editingItem.direction === 'out'">Outbound (Stock Decrease)</option>
                                    <option value="internal" :selected="editingItem && editingItem.direction === 'internal'">Internal Transfer</option>
                                </select>
                            </div>
                            @endif

                            @if(in_array('sort_order', $config['fields']))
                            <div>
                                <label class="block text-xs font-bold uppercase text-surface-700 mb-1">Sort Order</label>
                                <input type="number" name="sort_order" :value="editingItem ? editingItem.sort_order : 10" class="w-full rounded-xl border border-surface-300 px-3.5 py-2 text-sm focus:ring-2 focus:ring-brand-500">
                            </div>
                            @endif

                            @if(in_array('is_terminal', $config['fields']))
                            <div class="flex items-center gap-3 pt-2">
                                <input type="checkbox" name="is_terminal" value="1" :checked="editingItem && editingItem.is_terminal" id="check_is_terminal" class="rounded border-surface-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                                <label for="check_is_terminal" class="text-sm font-medium text-surface-800">Is Terminal Stage (End of Workflow)</label>
                            </div>
                            @endif
                        </div>

                        <x-dual-control.takes-effect op="lookup_update" class="mb-3" />
                        <div class="px-6 py-4 bg-white flex justify-end gap-3">
                            <button type="button" @click="openAddModal = false" class="px-4 py-2 rounded-xl border border-surface-300 text-surface-700 text-sm font-medium hover:bg-surface-50 transition-colors">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-xl bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium shadow-sm transition-all">Save Lookup Item</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
