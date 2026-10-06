<x-layouts.admin>
    <x-slot:title>Menus</x-slot:title>
    <x-slot:header>Settings — Menus</x-slot:header>

    @include('admin.settings._tabs')

    <div class="glass-card rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-4 bg-surface-50/80 border-b border-surface-200/60 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <h3 class="text-sm font-semibold text-surface-900">
                    <i class="fas fa-bars text-brand-500 mr-2"></i> Sidebar Menu Items
                </h3>
                <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-surface-100 text-surface-600">{{ $menus->count() }} items</span>
            </div>
            @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
            @if($canUpdateSetting)
            <a href="{{ route('admin.settings.menus.create') }}" class="inline-flex items-center justify-center rounded-lg bg-brand-600 px-3 py-1.5 text-sm font-semibold text-white shadow-sm hover:bg-brand-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-600">
                <i class="fas fa-plus mr-1.5"></i> Add Menu
            </a>
            @else
            <button disabled class="inline-flex items-center justify-center rounded-lg bg-slate-200 px-3 py-1.5 text-sm font-semibold text-slate-400 shadow-sm cursor-not-allowed" title="Add Menu (No Permission)">
                <i class="fas fa-plus mr-1.5"></i> Add Menu
            </button>
            @endif
        </div>

        @if($menus->count() > 0)
            <form method="POST" action="{{ route('admin.settings.menus.update-order') }}">
                @csrf
                <div class="divide-y divide-surface-100">
                    @foreach($menus as $menu)
                        <div x-data="{ open: false }" class="px-6 py-4 hover:bg-surface-50/30 transition-colors">
                            <input type="hidden" name="menus[{{ $loop->index }}][id]" value="{{ $menu->id }}">
                            <div class="flex items-center justify-between gap-4">
                                <div class="flex items-center gap-4 flex-1">
                                    <div class="flex items-center gap-2 text-surface-400">
                                        <i class="fas fa-grip-vertical cursor-grab hover:text-surface-600 transition-colors"></i>
                                        @if($menu->children->count() > 0)
                                            <button type="button" @click="open = !open" class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-surface-200 text-surface-400 transition-colors">
                                                <i class="fas fa-chevron-right text-xs transition-transform duration-200" :class="{'rotate-90': open}"></i>
                                            </button>
                                        @else
                                            <div class="w-6 h-6"></div> <!-- Spacer -->
                                        @endif
                                    </div>
                                    <div class="h-9 w-9 rounded-lg bg-surface-100 flex items-center justify-center">
                                        @if($menu->icon)
                                            <i class="fas {{ $menu->icon }} text-surface-500 text-sm"></i>
                                        @else
                                            <i class="fas fa-link text-surface-300 text-sm"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-surface-900">{{ $menu->title }}</p>
                                        <p class="text-xs text-surface-400 font-mono">{{ $menu->route ?? '—' }}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-4">
                                    @if($menu->permission_name)
                                        <span class="px-2 py-1 rounded text-[11px] font-medium bg-blue-50 text-blue-600 font-mono">{{ $menu->permission_name }}</span>
                                    @endif
                                    @if($menu->section)
                                        <span class="px-2 py-1 rounded text-[11px] font-medium bg-surface-100 text-surface-500 uppercase">{{ $menu->section }}</span>
                                    @endif
                                    <div class="w-20">
                                        <input type="number" name="menus[{{ $loop->index }}][sort_order]" value="{{ $menu->sort_order }}" min="0"
                                            class="w-full rounded-lg border-0 py-1.5 px-2 text-center text-sm text-surface-700 ring-1 ring-inset ring-surface-200 focus:ring-2 focus:ring-brand-500 bg-white">
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer mr-3">
                                        <input type="hidden" name="menus[{{ $loop->index }}][is_active]" value="0">
                                        <input type="checkbox" name="menus[{{ $loop->index }}][is_active]" value="1" {{ $menu->is_active ? 'checked' : '' }}
                                            class="sr-only peer">
                                        <div class="w-9 h-5 bg-surface-200 peer-focus:ring-4 peer-focus:ring-brand-200 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-surface-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-brand-500"></div>
                                    </label>
                                    <div class="flex items-center gap-2">
                                        @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
                                        @if($canUpdateSetting)
                                        <a href="{{ route('admin.settings.menus.edit', $menu) }}" class="p-1.5 text-surface-400 hover:text-brand-500 rounded-lg hover:bg-surface-100 transition-colors">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" @click="$dispatch('open-modal', 'delete-menu-{{ $menu->id }}')" class="p-1.5 text-surface-400 hover:text-red-500 rounded-lg hover:bg-red-50 transition-colors">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @else
                                        <button disabled class="p-1.5 text-surface-300 cursor-not-allowed" title="Edit (No Permission)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button disabled class="p-1.5 text-red-200 cursor-not-allowed" title="Delete (No Permission)">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            {{-- Delete Modal --}}
                            <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'delete-menu-{{ $menu->id }}') open = true">
                                <div x-show="open" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                    <div class="fixed inset-0 bg-surface-900/75 backdrop-blur-sm transition-opacity" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                                    <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                                        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" @click.away="open = false" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                                                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                                    <div class="sm:flex sm:items-start">
                                                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                            <i class="fas fa-exclamation-triangle text-red-600"></i>
                                                        </div>
                                                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                            <h3 class="text-base font-semibold leading-6 text-surface-900" id="modal-title">Delete Menu</h3>
                                                            <div class="mt-2">
                                                                <p class="text-sm text-surface-500">Are you sure you want to delete the menu <span class="font-bold">"{{ $menu->title }}"</span>? This action cannot be undone.</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="bg-surface-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                    <button type="button" onclick="document.getElementById('delete-form').action='{{ route('admin.settings.menus.destroy', $menu) }}'; document.getElementById('delete-form').submit();" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
                                                    <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-surface-900 shadow-sm ring-1 ring-inset ring-surface-300 hover:bg-surface-50 sm:mt-0 sm:w-auto">Cancel</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {{-- Children --}}
                            @if($menu->children->count() > 0)
                                <div x-show="open" x-collapse x-cloak class="ml-[4.5rem] mt-3 space-y-2 border-l-2 border-surface-100 pl-4 py-1">
                                    @foreach($menu->children as $child)
                                        <div class="flex items-center justify-between p-3 rounded-xl bg-white border border-surface-200/60 shadow-sm hover:border-brand-300/50 transition-all">
                                            <div class="flex items-center gap-3">
                                                <div class="w-7 h-7 rounded-lg bg-surface-50 flex items-center justify-center">
                                                    @if($child->icon) <i class="fas {{ $child->icon }} text-surface-500 text-[10px]"></i> @else <i class="fas fa-link text-surface-300 text-[10px]"></i> @endif
                                                </div>
                                                <div>
                                                    <span class="text-sm font-medium text-surface-800 block leading-none">{{ $child->title }}</span>
                                                    <span class="text-[10px] text-surface-400 font-mono mt-1 block">{{ $child->route ?? '—' }}</span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-4">
                                                @if($child->permission_name)
                                                    <span class="px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50/50 text-blue-500 font-mono">{{ $child->permission_name }}</span>
                                                @endif
                                                <span class="text-[10px] {{ $child->is_active ? 'text-emerald-500' : 'text-surface-300' }} flex items-center gap-1 bg-surface-50 px-2 py-0.5 rounded-full">
                                                    <i class="fas {{ $child->is_active ? 'fa-check-circle' : 'fa-circle' }}"></i> {{ $child->is_active ? 'Active' : 'Hidden' }}
                                                </span>
                                                <div class="flex items-center gap-1 border-l border-surface-200 pl-3">
                                                    @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
                                                    @if($canUpdateSetting)
                                                    <a href="{{ route('admin.settings.menus.edit', $child) }}" class="p-1 text-surface-400 hover:text-brand-500 rounded hover:bg-surface-200/50 transition-colors">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </a>
                                                    <button type="button" @click="$dispatch('open-modal', 'delete-menu-{{ $child->id }}')" class="p-1 text-surface-400 hover:text-red-500 rounded hover:bg-red-50 transition-colors">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                    @else
                                                    <button disabled class="p-1 text-surface-300 cursor-not-allowed" title="Edit (No Permission)">
                                                        <i class="fas fa-edit text-xs"></i>
                                                    </button>
                                                    <button disabled class="p-1 text-red-200 cursor-not-allowed" title="Delete (No Permission)">
                                                        <i class="fas fa-trash-alt text-xs"></i>
                                                    </button>
                                                    @endif
                                                </div>
                                                
                                                {{-- Child Delete Modal --}}
                                                <div x-data="{ open: false }" @open-modal.window="if ($event.detail === 'delete-menu-{{ $child->id }}') open = true">
                                                    <div x-show="open" x-cloak class="relative z-50" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                                                        <div class="fixed inset-0 bg-surface-900/75 backdrop-blur-sm transition-opacity" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>
                                                        <div class="fixed inset-0 z-10 w-screen overflow-y-auto">
                                                            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                                                                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg" @click.away="open = false" x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                                                                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                                                                        <div class="sm:flex sm:items-start">
                                                                            <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                                                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                                                                            </div>
                                                                            <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                                                                                <h3 class="text-base font-semibold leading-6 text-surface-900" id="modal-title">Delete Menu</h3>
                                                                                <div class="mt-2">
                                                                                    <p class="text-sm text-surface-500">Are you sure you want to delete the sub-menu <span class="font-bold">"{{ $child->title }}"</span>? This action cannot be undone.</p>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <div class="bg-surface-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                                                                        <button type="button" onclick="document.getElementById('delete-form').action='{{ route('admin.settings.menus.destroy', $child) }}'; document.getElementById('delete-form').submit();" class="inline-flex w-full justify-center rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-500 sm:ml-3 sm:w-auto">Delete</button>
                                                                        <button type="button" @click="open = false" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-surface-900 shadow-sm ring-1 ring-inset ring-surface-300 hover:bg-surface-50 sm:mt-0 sm:w-auto">Cancel</button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 border-t border-surface-100 flex justify-end">
                    @php $canUpdateSetting = auth()->user()?->hasPermission('setting:update'); @endphp
                    @if($canUpdateSetting)
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-brand-500 to-brand-600 px-6 py-2.5 text-sm font-semibold text-white shadow-lg shadow-brand-500/25 hover:shadow-brand-500/40 hover:-translate-y-0.5 transition-all">
                        <i class="fas fa-save text-xs"></i> Save Menu Order
                    </button>
                    @else
                    <button type="button" disabled class="inline-flex items-center gap-2 rounded-xl bg-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-400 shadow-sm cursor-not-allowed" title="Save Menu Order (No Permission)">
                        <i class="fas fa-save text-xs"></i> Save Menu Order
                    </button>
                    @endif
                </div>
            </form>
        @else
            <div class="p-12 text-center">
                <div class="inline-flex items-center justify-center h-14 w-14 rounded-full bg-surface-100 mb-4">
                    <i class="fas fa-bars text-surface-400 text-xl"></i>
                </div>
                <p class="text-sm text-surface-500">No menu items configured.</p>
                <p class="text-xs text-surface-400 mt-1">Menu items will appear here once seeded.</p>
            </div>
        @endif
    </div>
    
    <form id="delete-form" method="POST" class="hidden">
        @csrf
        @method('DELETE')
    </form>
</x-layouts.admin>
