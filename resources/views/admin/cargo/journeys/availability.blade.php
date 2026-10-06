<x-layouts.admin>
    <x-slot:title>Manage Availability: {{ $journey->route->origin->name }} - {{ $journey->route->destination->name }}</x-slot:title>
    <x-slot:header>Manage Availability</x-slot:header>

    <div class="mb-6">
        <a href="{{ route('admin.availability.index') }}" class="text-sm font-bold text-slate-500 hover:text-brand-600 flex items-center gap-2 transition-colors">
            <i class="fas fa-arrow-left"></i> Back to Journeys
        </a>
    </div>

    @if ($errors->any())
        <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-4 py-3 rounded-xl shadow-sm text-sm">
            <div class="font-bold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-circle"></i> Please fix the following errors:</div>
            <ul class="list-disc pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">
            {{ \Carbon\Carbon::parse($journey->departure_time)->format('H:i') }} | {{ $journey->route->origin->name }} &rarr; {{ $journey->route->destination->name }}
        </h2>
        <p class="text-slate-500 text-sm mt-1">Transport Unit: <span class="font-bold">{{ $journey->transportUnit->name }}</span> | Date: {{ \Carbon\Carbon::parse($journey->departure_date)->format('F j, Y') }}</p>
    </div>

    @if (session()->has('error'))
        <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-700 rounded-xl flex items-start gap-3 animate-fade-in-up">
            <i class="fas fa-exclamation-circle mt-1"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden p-6">
        <form method="POST" action="{{ route('admin.cargo.journeys.availability.update', $journey->id) }}">
            @csrf
            
            <div class="space-y-6">
                @foreach($inventories as $i => $item)
                    <div class="p-5 border border-slate-100 rounded-2xl bg-slate-50/50 shadow-sm">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="font-bold text-lg text-slate-800">{{ $item['class']->name }}</h3>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Base Capacity: {{ $item['class']->capacity }}</p>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-black {{ $item['available'] > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $item['available'] }} <span class="text-sm text-slate-500 font-semibold">available</span>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                            <div class="bg-white p-3 rounded-xl border border-slate-200 text-center shadow-sm">
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Sold</div>
                                <div class="text-xl font-bold text-slate-700">{{ $item['inventory']->sold_seats }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200 text-center shadow-sm">
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Held (Locks)</div>
                                <div class="text-xl font-bold text-slate-700">{{ $item['inventory']->held_seats }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200 text-center shadow-sm">
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Blocked</div>
                                <div class="text-xl font-bold text-slate-700">{{ $item['inventory']->blocked_seats }}</div>
                            </div>
                            <div class="bg-white p-3 rounded-xl border border-slate-200 text-center shadow-sm border-l-4 border-l-brand-500 relative">
                                <div class="text-xs text-slate-500 font-bold uppercase tracking-wider mb-1">Override Total</div>
                                <input type="hidden" name="inventories[{{ $i }}][id]" value="{{ $item['inventory']->id }}">
                                <input type="number" 
                                       name="inventories[{{ $i }}][total_seats]" 
                                       value="{{ $item['inventory']->total_seats }}" 
                                       min="0"
                                       class="w-full text-center text-xl font-bold text-brand-700 bg-brand-50 border-none focus:ring-0 p-0"
                                >
                                <div class="absolute -top-2 -right-2 bg-brand-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full shadow-sm">
                                    Editable
                                </div>
                            </div>
                        </div>
                        
                        @if($item['inventory']->total_seats != $item['class']->capacity)
                            <div class="text-xs font-semibold text-amber-600 bg-amber-50 px-3 py-2 rounded-lg border border-amber-100 flex items-center gap-2 mb-4">
                                <i class="fas fa-exclamation-triangle"></i> 
                                This class is currently oversold/adjusted by {{ $item['inventory']->total_seats - $item['class']->capacity }} seats.
                            </div>
                        @endif

                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <h4 class="text-sm font-bold text-slate-700 mb-2 flex items-center gap-2">
                                <i class="fas fa-users"></i> Extra Seats Agent Allocations
                            </h4>
                            <p class="text-xs text-slate-500 mb-3">Assign portions of the extra capacity to specific agents so they can sell beyond the base capacity.</p>
                            
                            @php
                                $allocs = $item['inventory']->allocations;
                                $rowCount = max(count($allocs), 1);
                            @endphp
                            
                            <div class="space-y-2" id="allocations-container-{{ $i }}">
                                @for($a = 0; $a < $rowCount; $a++)
                                    @php $alloc = $allocs[$a] ?? null; @endphp
                                    <div class="flex gap-3">
                                        <select name="inventories[{{ $i }}][allocations][{{ $a }}][agent_id]" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                                            <option value="">-- No Agent Selected --</option>
                                            @foreach($agents as $agent)
                                                <option value="{{ $agent->id }}" {{ $alloc && $alloc->agent_id == $agent->id ? 'selected' : '' }}>
                                                    {{ $agent->agency_name }} ({{ $agent->agent_code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div class="relative w-32 flex items-center gap-2">
                                            <input type="number" 
                                                   name="inventories[{{ $i }}][allocations][{{ $a }}][allocated_seats]" 
                                                   value="{{ $alloc ? $alloc->allocated_seats : '' }}" 
                                                   min="0" 
                                                   placeholder="Seats" 
                                                   class="w-full rounded-lg border border-slate-300 shadow-sm text-sm p-2 text-center"
                                            >
                                            <button type="button" class="text-slate-400 hover:text-red-500" onclick="this.parentElement.parentElement.remove()" title="Remove row">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endfor
                            </div>
                            <button type="button" class="mt-3 text-xs font-bold text-brand-600 hover:text-brand-700 flex items-center gap-1" onclick="addAllocationRow({{ $i }})">
                                <i class="fas fa-plus-circle"></i> Add Another Agent
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8 pt-6 border-t border-slate-100 flex justify-end gap-3">
                <a href="{{ route('admin.availability.index') }}" class="px-6 py-2.5 rounded-xl border border-slate-300 text-slate-700 font-bold hover:bg-slate-50 transition-colors">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-brand-600 text-white font-bold hover:bg-brand-700 shadow-sm transition-all flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Capacity Changes
                </button>
            </div>

            <script>
                const agentsData = @json($agents->map(fn($a) => ['id' => $a->id, 'name' => $a->agency_name, 'code' => $a->agent_code]));
                let allocCounters = {};
                
                function addAllocationRow(inventoryIndex) {
                    const container = document.getElementById('allocations-container-' + inventoryIndex);
                    
                    if (!allocCounters[inventoryIndex]) {
                        allocCounters[inventoryIndex] = container.children.length;
                    }
                    const a = allocCounters[inventoryIndex]++;
                    
                    let options = '<option value="">-- No Agent Selected --</option>';
                    agentsData.forEach(agent => {
                        options += `<option value="${agent.id}">${agent.name} (${agent.code})</option>`;
                    });
                    
                    const rowHTML = `
                        <div class="flex gap-3 mt-2">
                            <select name="inventories[${inventoryIndex}][allocations][${a}][agent_id]" class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors">
                                ${options}
                            </select>
                            <div class="relative w-32 flex items-center gap-2">
                                <input type="number" 
                                       name="inventories[${inventoryIndex}][allocations][${a}][allocated_seats]" 
                                       min="0" 
                                       placeholder="Seats" 
                                       class="w-full rounded-xl border-slate-200 bg-slate-50 hover:bg-white focus:bg-white px-4 py-3.5 text-sm font-medium text-slate-900 shadow-sm focus:border-brand-500 focus:ring-brand-500 transition-colors"
                                >
                                <button type="button" class="text-slate-400 hover:text-red-500" onclick="this.parentElement.parentElement.remove()" title="Remove row">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    
                    container.insertAdjacentHTML('beforeend', rowHTML);
                }
            </script>    
            <p class="text-xs text-slate-400 text-right mt-3 flex items-center justify-end gap-1">
                <i class="fas fa-shield-alt"></i> Requires Dual Control Approval
            </p>
        </form>
    </div>
</x-layouts.admin>
