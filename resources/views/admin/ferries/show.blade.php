<x-layouts.admin>
    <x-slot:title>Ferry Profile: {{ $ferry->name }}</x-slot:title>
    <x-slot:header>Fleet Management</x-slot:header>

    @php
        $cert = $ferry->certificates()->latest('issued_date')->first();
    @endphp

    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.ferries.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                <i class="fas fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h2 class="text-2xl font-bold text-slate-800">{{ $ferry->name }}</h2>
                <p class="text-slate-500 text-sm">Registry: {{ $ferry->port_of_registry ?? 'N/A' }} &bull; Type: {{ $ferry->type ?? 'Passenger Ferry' }}</p>
            </div>
        </div>
        <div>
            <span class="inline-flex items-center rounded-md {{ match($ferry->status) {
                                        'active' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'maintenance' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        default => 'bg-slate-50 text-slate-600 ring-slate-500/10'
                                    } }} px-2 py-1 text-xs font-medium ring-1 ring-inset uppercase tracking-wider">
                {{ $ferry->status }}
            </span>
        </div>
    </div>



    <div x-data="{ 
        activeTab: 'details', 
        showCertModal: false 
    }">
    
        <!-- Certificate Modal -->
        <div x-show="showCertModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="showCertModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0" 
                     x-transition:enter-end="opacity-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100" 
                     x-transition:leave-end="opacity-0" 
                     class="fixed inset-0 bg-slate-900/75 transition-opacity" 
                     @click="showCertModal = false"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="showCertModal" 
                     x-transition:enter="ease-out duration-300" 
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave="ease-in duration-200" 
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                     class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                    
                    <form action="{{ route('admin.ferries.update-certificate', $ferry) }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                    <h3 class="text-lg leading-6 font-bold text-slate-900" id="modal-title">
                                        Manage Safe Manning Certificate
                                    </h3>
                                    <div class="mt-4 space-y-4">
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Certificate Number</label>
                                            <input type="text" name="certificate_number" value="{{ $cert->certificate_number ?? '' }}" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-1">Issued Date</label>
                                                <input type="date" name="issued_date" value="{{ $cert && $cert->issued_date ? $cert->issued_date->format('Y-m-d') : '' }}" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-1">Expiry Date</label>
                                                <input type="date" name="expiry_date" value="{{ $cert && $cert->expiry_date ? $cert->expiry_date->format('Y-m-d') : '' }}" required class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                            </div>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-1">Total Crews</label>
                                                <input type="number" name="max_crew" value="{{ $cert->max_crew ?? '' }}" required min="1" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-slate-700 mb-1">Max Passengers</label>
                                                <input type="number" name="max_passengers" value="{{ $cert->max_passengers ?? '' }}" min="1" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Issued By (Authority)</label>
                                            <input type="text" name="issued_by" value="{{ $cert->issued_by ?? '' }}" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-slate-700 mb-1">Notes / Remarks</label>
                                            <textarea name="notes" rows="2" class="w-full bg-slate-50 border border-slate-200 text-slate-800 rounded-xl px-4 py-2.5 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 outline-none transition-all">{{ $cert->notes ?? '' }}</textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-4 sm:px-6 sm:flex sm:flex-row-reverse border-t border-slate-200">
                            <button type="submit" class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-brand-600 text-base font-medium text-white hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-brand-500 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Save Certificate
                            </button>
                            <button type="button" @click="showCertModal = false" class="mt-3 w-full inline-flex justify-center rounded-xl border border-slate-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-slate-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden min-h-[600px]">
            <!-- Tabs Header -->
            <div class="border-b border-slate-200 bg-slate-50/50">
            <nav class="-mb-px flex space-x-8 px-8" aria-label="Tabs">
                <button @click="activeTab = 'details'"
                        :class="activeTab === 'details' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-info-circle mr-2"></i> Ferry Details
                </button>
                <button @click="activeTab = 'layout'"
                        :class="activeTab === 'layout' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-th mr-2"></i> Interactive Layout
                </button>
                <button @click="activeTab = 'classes'"
                        :class="activeTab === 'classes' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-couch mr-2"></i> Classes
                </button>
                <button @click="activeTab = 'schedules'"
                        :class="activeTab === 'schedules' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-calendar-alt mr-2"></i> Schedules
                </button>
                <button @click="activeTab = 'prices'"
                        :class="activeTab === 'prices' ? 'border-brand-500 text-brand-600' : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700'"
                        class="whitespace-nowrap border-b-2 py-4 px-1 text-sm font-medium transition-colors">
                    <i class="fas fa-tags mr-2"></i> Route Prices
                </button>
            </nav>
        </div>

        <!-- Tab Contents -->
        <div class="p-8">
            <!-- Ferry Details -->
            <div x-show="activeTab === 'details'" x-cloak>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    
                    <!-- Basic Info -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 h-full">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-200">
                            <i class="fas fa-ship text-brand-500 text-xl"></i>
                            <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Vessel Specifications</h3>
                        </div>
                        
                        <div class="space-y-4">
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Vessel Name</span>
                                <span class="text-slate-800 font-semibold">{{ $ferry->name }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Type</span>
                                <span class="text-slate-800 font-semibold">{{ $ferry->type ?? 'Passenger Ferry' }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Port of Registry</span>
                                <span class="text-slate-800 font-semibold">{{ $ferry->port_of_registry ?? 'N/A' }}</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Total Capacity</span>
                                <span class="text-slate-800 font-bold text-brand-600">{{ number_format($ferry->capacity) }} Pax</span>
                            </div>
                            <div class="grid grid-cols-2">
                                <span class="text-slate-500 text-sm font-medium">Added Date</span>
                                <span class="text-slate-800 font-semibold">{{ $ferry->created_at->format('j M Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Certificates Dummy Data (as per screenshot) -->
                    <div class="p-6 bg-slate-50 rounded-2xl border border-slate-100 h-full">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b border-slate-200">
                            <div class="flex items-center gap-3">
                                <i class="fas fa-certificate text-amber-500 text-xl"></i>
                                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wider">Safe Manning Certificate</h3>
                            </div>
                            @php $canUpdateFerry = auth()->user()->hasPermission('ferry:update'); @endphp
                            @if($canUpdateFerry)
                            <button @click="showCertModal = true" class="text-xs font-semibold text-brand-600 hover:text-brand-700 transition-colors bg-brand-50 px-3 py-1.5 rounded-lg border border-brand-100">
                                Manage
                            </button>
                            @else
                            <button disabled class="text-xs font-semibold text-slate-400 bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-200 cursor-not-allowed" title="Manage (No Permission)">
                                Manage
                            </button>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @if($cert)
                                <div class="grid grid-cols-2">
                                    <span class="text-slate-500 text-sm font-medium">Certificate No.</span>
                                    <span class="text-slate-800 font-semibold">{{ $cert->certificate_number }}</span>
                                </div>
                                <div class="grid grid-cols-2">
                                    <span class="text-slate-500 text-sm font-medium">Issued Date</span>
                                    <span class="text-slate-800 font-semibold">{{ $cert->issued_date ? $cert->issued_date->format('d M Y') : '-' }}</span>
                                </div>
                                <div class="grid grid-cols-2">
                                    <span class="text-slate-500 text-sm font-medium">Expiry Date</span>
                                    <span class="{{ $cert->expiry_date && $cert->expiry_date->isPast() ? 'text-rose-600' : 'text-emerald-600' }} font-semibold">
                                        {{ $cert->expiry_date ? $cert->expiry_date->format('d M Y') : '-' }}
                                    </span>
                                </div>
                                <div class="grid grid-cols-2">
                                    <span class="text-slate-500 text-sm font-medium">Total Crews</span>
                                    <span class="text-slate-800 font-semibold">{{ $cert->max_crew ?? '-' }}</span>
                                </div>
                            @else
                                <div class="text-center py-4 text-slate-500 text-sm">
                                    No active certificate uploaded.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Seat Layout -->
            <div x-show="activeTab === 'layout'" x-cloak>
                <div class="mb-6 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Digital Interactive Map</h3>
                        <p class="text-sm text-slate-500">Design and assign seats in the Layout Studio</p>
                    </div>
                    @if($ferry->layout_image_path)
                    <a href="{{ Storage::url($ferry->layout_image_path) }}" target="_blank" class="text-sm text-brand-600 hover:text-brand-700 font-semibold bg-brand-50 px-4 py-2 rounded-xl border border-brand-100 flex items-center gap-2">
                        <i class="fas fa-image"></i> View Original Upload
                    </a>
                    @endif
                </div>

                <div class="p-12 text-center bg-slate-50 rounded-2xl border border-slate-200 border-dashed">
                    <i class="fas fa-object-ungroup text-4xl text-brand-500 mb-4"></i>
                    <h3 class="text-xl font-bold text-slate-800 mb-2">Vessel Layout Studio</h3>
                    <p class="text-sm text-slate-500 max-w-md mx-auto mb-6">Enter the interactive studio to automatically generate AI layouts, manually adjust seats, draw walls, and publish your deck map.</p>
                    
                    <a href="{{ route('admin.ferries.editor', $ferry->id) }}" class="inline-flex items-center gap-2 px-6 py-3 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-sm transition-colors">
                        <i class="fas fa-external-link-alt"></i> Open Layout Studio
                    </a>
                </div>
            </div>

            <!-- Classes (Livewire) -->
            <div x-show="activeTab === 'classes'" x-cloak>
                <livewire:admin.ferry-class-manager :ferry="$ferry" />
            </div>

            <!-- Schedules (Livewire) -->
            <div x-show="activeTab === 'schedules'" x-cloak>
                <livewire:admin.ferry-schedule-manager :ferry="$ferry" />
            </div>
            
            <!-- Route Prices (Livewire) -->
            <div x-show="activeTab === 'prices'" x-cloak>
                <livewire:admin.ferry-pricing-manager :ferry="$ferry" />
            </div>
        </div>
    </div>
    </div>
</x-layouts.admin>
