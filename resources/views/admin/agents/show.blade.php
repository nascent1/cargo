<x-layouts.admin>
    <x-slot:title>Manage Agency: {{ $agent->agency_name }}</x-slot:title>
    <x-slot:header>
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.agents.index') }}" class="h-9 w-9 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 flex items-center justify-center transition">
                    <i class="fas fa-arrow-left text-sm"></i>
                </a>
                <div>
                    <h2 class="font-bold text-lg text-slate-900">{{ $agent->agency_name }}</h2>
                    <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-brand-50 text-brand-700 border border-brand-200">
                        Code: {{ $agent->agent_code }}
                    </span>
                </div>
            </div>
        </div>
    </x-slot:header>

    @if (session('success'))
        <div class="mb-6 rounded-xl bg-emerald-50 border border-emerald-200 p-4 text-emerald-800 flex items-center gap-3">
            <i class="fas fa-check-circle text-emerald-600 text-lg"></i>
            <p class="text-sm font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-xl bg-rose-50 border border-rose-200 p-4 text-rose-800">
            <p class="font-bold mb-1 flex items-center gap-2"><i class="fas fa-exclamation-triangle"></i> Please fix the following errors:</p>
            <ul class="list-disc list-inside text-sm space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Profile & Financial Management Container (Scoped outside header z-40) -->
    <div x-data="{ showTopupModal: false, showLimitModal: false }">
        <!-- Dual Wallet Balance Overview -->
        @if($agent->account)
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-6">
                <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-2xl p-6 text-white shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400">Virtual Float Balance</span>
                        <h3 class="font-mono text-3xl font-extrabold mt-2 {{ $agent->account->balance < 100000 ? 'text-amber-400' : 'text-emerald-400' }}">
                            {{ number_format($agent->account->balance, 2) }} <span class="text-sm font-normal">{{ $agent->account->currency }}</span>
                        </h3>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-700/80 flex items-center justify-between text-xs text-slate-400">
                        <span>Account: {{ $agent->account->account_number }}</span>
                        <button @click="showTopupModal = true" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white font-bold shadow transition flex items-center gap-1.5 text-xs">
                            <i class="fas fa-wallet"></i> Top-Up Float
                        </button>
                    </div>
                </div>

                <div class="bg-indigo-950 rounded-2xl p-6 text-white shadow-sm flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-wider text-indigo-300">Security Deposit Reserve</span>
                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-900 text-indigo-200 border border-indigo-700">
                                {{ number_format($agent->getEffectiveSecurityDepositRate(), 1) }}% Hold
                            </span>
                        </div>
                        <h3 class="font-mono text-3xl font-extrabold text-indigo-200 mt-2">
                            {{ number_format($agent->account->security_deposit_balance ?? 0, 2) }} <span class="text-sm font-normal">{{ $agent->account->currency }}</span>
                        </h3>
                    </div>
                    <div class="mt-4 pt-3 border-t border-indigo-900 flex items-center justify-between text-xs text-indigo-300">
                        <span>Earned Comm ({{ strtoupper($agent->getEffectiveCommissionMethod()) }}: {{ number_format($agent->getEffectiveCommissionRate(), 1) }}%)</span>
                        <span class="font-mono font-bold text-white">{{ number_format($agent->getTotalEarnedCommission(), 2) }} {{ $agent->account->currency }}</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Overdraft Credit Limit</span>
                        <h3 class="font-mono text-3xl font-extrabold text-slate-800 mt-2">
                            {{ number_format($agent->account->credit_limit, 2) }} <span class="text-sm font-normal text-slate-500">{{ $agent->account->currency }}</span>
                        </h3>
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
                        <span>Allowed ticketing before top-up</span>
                        <button @click="showLimitModal = true" class="px-3 py-1.5 rounded-lg bg-slate-900 hover:bg-slate-800 text-white font-bold shadow transition flex items-center gap-1.5 text-xs">
                            <i class="fas fa-sliders-h text-brand-400"></i> Adjust Limit
                        </button>
                    </div>
                </div>

                <div class="bg-brand-50/80 rounded-2xl p-6 border border-brand-200/80 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-brand-800">Total Purchasing Power</span>
                        <h3 class="font-mono text-3xl font-extrabold text-brand-900 mt-2">
                            {{ number_format($agent->account->balance + $agent->account->credit_limit, 2) }} <span class="text-sm font-normal text-brand-700">{{ $agent->account->currency }}</span>
                        </h3>
                    </div>
                    <div class="mt-4 pt-3 border-t border-brand-200 flex items-center justify-between text-xs text-brand-700">
                        <span>Available funds for ticketing</span>
                        <span class="font-bold">Float + Credit Limit</span>
                    </div>
                </div>
            </div>
        @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: Agency Profile & Staff Hierarchy -->
        <div class="space-y-6">
            <!-- Agency Profile Details Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm" x-data="{ editing: false }">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100 flex-wrap gap-2">
                    <h3 class="font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-building text-brand-600"></i> Agency Profile
                    </h3>
                    <div class="flex items-center gap-2">
                        @if($agent->account)
                            <button @click="showLimitModal = true" class="px-2.5 py-1 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition flex items-center gap-1">
                                <i class="fas fa-sliders-h"></i> Adjust Credit
                            </button>
                            <button @click="showTopupModal = true" class="px-3 py-1 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold shadow transition flex items-center gap-1">
                                <i class="fas fa-wallet"></i> Top-Up Float
                            </button>
                        @endif
                        <button @click="editing = !editing" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition ml-1">
                            <span x-show="!editing"><i class="fas fa-edit mr-1"></i> Edit</span>
                            <span x-show="editing" style="display: none;"><i class="fas fa-times mr-1"></i> Cancel</span>
                        </button>
                    </div>
                </div>

                <!-- Display Mode -->
                <div x-show="!editing" class="mt-4 space-y-3 text-sm">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Agency Name:</span>
                        <span class="font-bold text-slate-800">{{ $agent->agency_name }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Partner Type:</span>
                        <span class="font-bold uppercase text-brand-600">{{ $agent->agent_type }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Commission Method:</span>
                        <span class="font-bold text-slate-800 uppercase">{{ $agent->getEffectiveCommissionMethod() }} {{ $agent->commission_method ? '(Exclusive)' : '(Default)' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Commission Rate:</span>
                        <span class="font-bold text-slate-800">{{ $agent->getEffectiveCommissionRate() }}% {{ $agent->commission_rate !== null ? '(Exclusive)' : '(Default)' }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Security Deposit Hold:</span>
                        <span class="font-bold text-slate-800">{{ $agent->getEffectiveSecurityDepositRate() }}% <span class="text-xs font-normal text-slate-400">(System Global Configuration)</span></span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Primary Email:</span>
                        <span class="font-semibold text-slate-800">{{ $agent->email ?? $agent->user?->email }}</span>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Contact Mobile:</span>
                        <span class="font-semibold text-slate-800">{{ $agent->contact_mobile ?? '-' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Office Address:</span>
                        <span class="font-semibold text-slate-800 text-right max-w-xs">{{ $agent->address ?? '-' }}</span>
                    </div>
                </div>

                <!-- Edit Mode Form -->
                <form x-show="editing" action="{{ route('admin.agents.update', $agent) }}" method="POST" class="mt-4 space-y-3 text-sm" style="display: none;">
                    @csrf
                    <x-dual-control.takes-effect op="agent_update" />
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Agency Name</label>
                        <input type="text" name="agency_name" value="{{ $agent->agency_name }}" required
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Partner Type</label>
                        <select name="agent_type" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                            <option value="direct" {{ $agent->agent_type == 'direct' ? 'selected' : '' }}>Direct Partner</option>
                            <option value="reseller" {{ $agent->agent_type == 'reseller' ? 'selected' : '' }}>Reseller</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Commission Method</label>
                        <select name="commission_method" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                            <option value="" {{ empty($agent->commission_method) ? 'selected' : '' }}>System Default (Post-Sale)</option>
                            <option value="post_sale" {{ $agent->commission_method === 'post_sale' ? 'selected' : '' }}>Exclusive Post-Sale (% on Ticket Sale)</option>
                            <option value="pre_deposit" {{ $agent->commission_method === 'pre_deposit' ? 'selected' : '' }}>Exclusive Upfront Pre-Deposit (% on Top-Up)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Commission Rate (%) <span class="text-slate-400 font-normal">(Leave blank for Default)</span></label>
                        <input type="number" step="0.5" min="0" max="100" name="commission_rate" value="{{ $agent->commission_rate }}" placeholder="System Default ({{ \App\Models\SystemConfig::getValue('default_agent_commission_percent', 5) }}%)"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm font-mono text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Contact Mobile</label>
                        <input type="text" name="contact_mobile" value="{{ $agent->contact_mobile }}"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Address</label>
                        <textarea name="address" rows="2" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">{{ $agent->address }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Status</label>
                        <select name="is_active" class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                            <option value="1" {{ $agent->is_active ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ !$agent->is_active ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-bold text-xs hover:bg-brand-700 transition shadow">
                            <i class="fas fa-save mr-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>

            <!-- Associate Staff Hierarchy Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm" x-data="{ showAssociateModal: false }">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-users text-brand-600"></i> Associate Staff Directory
                    </h3>
                    <button @click="showAssociateModal = true" class="px-3 py-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold transition flex items-center gap-1">
                        <i class="fas fa-plus"></i> Add Staff
                    </button>
                </div>

                <div class="mt-4 space-y-3">
                    @forelse($agent->associates as $associate)
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100" x-data="{ editStaff: false }">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                        {{ $associate->associate_name }}
                                        @if(!$associate->is_active)
                                            <span class="px-1.5 py-0.5 text-[10px] bg-rose-100 text-rose-700 rounded font-semibold">Inactive</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-slate-500 font-mono">{{ $associate->email ?? $associate->user?->email }} | {{ $associate->contact_mobile ?? '-' }}</p>
                                </div>
                                <div class="flex items-center gap-3">
                                    <div class="text-right">
                                        <span class="font-mono text-xs font-bold text-slate-700 block">{{ number_format($associate->daily_credit_limit, 0) }} TZS</span>
                                        <span class="text-[10px] text-slate-400">Daily Limit</span>
                                    </div>
                                    <button @click="editStaff = !editStaff" class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 hover:bg-slate-200 transition text-xs">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Edit Staff Form -->
                            <div x-show="editStaff" class="mt-3 pt-3 border-t border-slate-200 space-y-3" style="display: none;">
                                <form action="{{ route('admin.agents.update-associate', [$agent, $associate]) }}" method="POST" class="space-y-3 text-xs">
                                    @csrf
                                    @method('PUT')
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        <div>
                                            <label class="block font-bold text-slate-700 mb-1">Staff Full Name</label>
                                            <input type="text" name="associate_name" value="{{ $associate->associate_name }}" required
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                        </div>
                                        <div>
                                            <label class="block font-bold text-slate-700 mb-1">Contact Mobile</label>
                                            <input type="text" name="contact_mobile" value="{{ $associate->contact_mobile }}"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                        </div>
                                        <div>
                                            <label class="block font-bold text-slate-700 mb-1">Daily Limit (TZS)</label>
                                            <input type="number" step="1000" min="0" name="daily_credit_limit" value="{{ $associate->daily_credit_limit }}"
                                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 font-mono text-xs text-slate-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                        </div>
                                        <div>
                                            <label class="block font-bold text-slate-700 mb-1">Status</label>
                                            <select name="is_active" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                                                <option value="1" {{ $associate->is_active ? 'selected' : '' }}>Active</option>
                                                <option value="0" {{ !$associate->is_active ? 'selected' : '' }}>Suspended</option>
                                            </select>
                                        </div>
                                    </div>
                                    @php $approveRemove ??= app(\App\Services\DualControlService::class)->requiresDualControl('associate_update'); @endphp
                                    <div class="flex items-center justify-between pt-1">
                                        <button type="submit" form="del-associate-{{ $associate->id }}"
                                            data-confirm="Their login is deactivated.{{ $approveRemove ? ' A checker must approve it first.' : '' }}"
                                            data-confirm-title="Remove {{ $associate->associate_name }}?" data-confirm-button="{{ $approveRemove ? 'Request removal' : 'Remove' }}"
                                            data-confirm-tone="danger" data-confirm-icon="fa-user-minus" class="text-rose-600 hover:text-rose-700 font-bold text-[11px] flex items-center gap-1">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                        <div class="flex gap-2">
                                            <button type="button" @click="editStaff = false" class="px-3 py-1.5 rounded-lg bg-slate-200 text-slate-700 font-bold">Cancel</button>
                                            <button type="submit" class="px-3 py-1.5 rounded-lg bg-brand-600 text-white font-bold shadow hover:bg-brand-700">Save</button>
                                        </div>
                                    </div>
                                </form>
                                <form id="del-associate-{{ $associate->id }}" action="{{ route('admin.agents.destroy-associate', [$agent, $associate]) }}" method="POST" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="py-6 text-center text-slate-400 text-xs italic">
                            No associate staff registered yet.
                        </div>
                    @endforelse
                </div>

                <!-- Add Associate Modal -->
                <div x-show="showAssociateModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div x-show="showAssociateModal" @click="showAssociateModal = false" class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-sm"></div>
                        <div x-show="showAssociateModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200">
                            <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white">
                                <h3 class="font-bold text-base flex items-center gap-2">
                                    <i class="fas fa-user-plus text-brand-400"></i> Register Associate Staff Member
                                </h3>
                                <button @click="showAssociateModal = false" class="text-slate-400 hover:text-white transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                            <form action="{{ route('admin.agents.store-associate', $agent) }}" method="POST" class="p-6 space-y-4">
                                @csrf
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Staff Full Name *</label>
                                        <input type="text" name="associate_name" required placeholder="e.g. Mary Smith"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">First Name *</label>
                                        <input type="text" name="first_name" required placeholder="Mary"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Last Name *</label>
                                        <input type="text" name="last_name" required placeholder="Smith"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Address *</label>
                                        <input type="email" name="email" required placeholder="mary@agency.com"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Contact Mobile</label>
                                        <input type="text" name="contact_mobile" placeholder="+255 712 000 111"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                    <div class="md:col-span-2">
                                        <label class="block text-xs font-bold text-slate-700 mb-1">Daily Ticketing Credit Limit (TZS)</label>
                                        <input type="number" step="1000" min="0" name="daily_credit_limit" placeholder="Optional (e.g. 500000)"
                                            class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                    </div>
                                </div>
                                <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                                    <button type="button" @click="showAssociateModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs hover:bg-slate-200 transition">
                                        Cancel
                                    </button>
                                    <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 shadow transition">
                                        <i class="fas fa-save mr-1"></i> Register Staff
                                    </button>
                                </div>
                            </form>
                        </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assigned Fleet / Authorized Ferries Card -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm" x-data="{ editFleet: false }">
                <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900 flex items-center gap-2">
                        <i class="fas fa-ship text-brand-600"></i> Authorized Ferries
                    </h3>
                    <button @click="editFleet = !editFleet" class="text-xs font-bold text-brand-600 hover:text-brand-700 transition">
                        <span x-show="!editFleet"><i class="fas fa-edit mr-1"></i> Manage Fleet</span>
                        <span x-show="editFleet" style="display: none;"><i class="fas fa-times mr-1"></i> Cancel</span>
                    </button>
                </div>

                <!-- Display Mode -->
                <div x-show="!editFleet" class="mt-4 space-y-2">
                    @php $assignedFerries = $agent->ferries->keyBy('id'); @endphp
                    @forelse($agent->ferries as $ferry)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border text-xs {{ $ferry->pivot->status === 'active' ? 'bg-slate-50 border-slate-100' : 'bg-red-50 border-red-100' }}">
                            <span class="font-bold flex items-center gap-2 {{ $ferry->pivot->status === 'active' ? 'text-slate-800' : 'text-red-700' }}">
                                <i class="fas {{ $ferry->pivot->status === 'active' ? 'fa-check-circle text-emerald-600' : 'fa-ban text-red-500' }}"></i> {{ $ferry->name }}
                            </span>
                            <span class="font-mono text-slate-500 uppercase">{{ $ferry->pivot->status }}</span>
                        </div>
                    @empty
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                            <i class="fas fa-exclamation-triangle mr-1.5"></i> No ferries assigned yet. Agency cannot book trips until at least one vessel is assigned.
                        </div>
                    @endforelse
                </div>

                <!-- Edit Mode Form -->
                <form x-show="editFleet" action="{{ route('admin.agents.update-ferries', $agent) }}" method="POST" class="mt-4 space-y-3" style="display: none;">
                    @csrf
                    <x-dual-control.takes-effect op="agent_ferries_update" />
                    @method('PUT')
                    <p class="text-xs text-slate-500 mb-2">Select the vessels that {{ $agent->agency_name }} is authorized to issue tickets for, and set their status:</p>
                    
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach($allFerries as $ferry)
                            @php $isAssigned = $assignedFerries->has($ferry->id); $status = $isAssigned ? $assignedFerries->get($ferry->id)->pivot->status : 'active'; @endphp
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-2.5 rounded-xl border {{ $isAssigned ? 'bg-brand-50/50 border-brand-200' : 'bg-white border-slate-200' }} transition text-xs gap-2">
                                <label class="flex items-center gap-2.5 cursor-pointer flex-1">
                                    <input type="hidden" name="ferries[{{ $ferry->id }}][selected]" value="0">
                                    <input type="checkbox" name="ferries[{{ $ferry->id }}][selected]" value="1" {{ $isAssigned ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                                    <span class="font-bold text-slate-800">{{ $ferry->name }}</span>
                                    <span class="font-mono text-slate-400 text-[10px] uppercase ml-2">{{ $ferry->type ?? 'Vessel' }}</span>
                                </label>
                                <select name="ferries[{{ $ferry->id }}][status]" class="text-xs border-slate-300 rounded-lg py-1 px-2 focus:ring-brand-500">
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-bold text-xs hover:bg-brand-700 transition shadow flex items-center gap-1.5">
                            <i class="fas fa-save"></i> Save Authorization
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Route Authorizations -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-5" x-data="{ editRoutes: false }">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="font-bold text-slate-800 flex items-center gap-2 text-sm">
                        <i class="fas fa-map-marked-alt text-brand-500"></i> Route Authorizations
                    </h3>
                    <button @click="editRoutes = !editRoutes" type="button" class="text-xs font-bold text-brand-600 hover:text-brand-800 transition px-3 py-1 rounded-lg hover:bg-brand-50 border border-transparent hover:border-brand-200">
                        <i class="fas" :class="editRoutes ? 'fa-times' : 'fa-edit'"></i> <span x-text="editRoutes ? 'Cancel' : 'Manage'"></span>
                    </button>
                </div>

                <!-- Display Mode -->
                <div x-show="!editRoutes" class="mt-4 space-y-2">
                    @php $assignedRoutes = $agent->routes->keyBy('id'); @endphp
                    @forelse($agent->routes as $route)
                        <div class="flex items-center justify-between p-2.5 rounded-xl border text-xs {{ $route->pivot->status === 'active' ? 'bg-slate-50 border-slate-100' : 'bg-red-50 border-red-100' }}">
                            <span class="font-bold flex items-center gap-2 {{ $route->pivot->status === 'active' ? 'text-slate-800' : 'text-red-700' }}">
                                <i class="fas {{ $route->pivot->status === 'active' ? 'fa-check-circle text-emerald-600' : 'fa-ban text-red-500' }}"></i> {{ $route->name }}
                            </span>
                            <span class="font-mono text-slate-500 uppercase">{{ $route->pivot->status }}</span>
                        </div>
                    @empty
                        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs">
                            <i class="fas fa-exclamation-triangle mr-1.5"></i> No routes assigned yet. Agency cannot book trips until at least one route is assigned.
                        </div>
                    @endforelse
                </div>

                <!-- Edit Mode Form -->
                <form x-show="editRoutes" action="{{ route('admin.agents.update-routes', $agent) }}" method="POST" class="mt-4 space-y-3" style="display: none;">
                    @csrf
                    <x-dual-control.takes-effect op="agent_routes_update" />
                    @method('PUT')
                    <p class="text-xs text-slate-500 mb-2">Select the routes that {{ $agent->agency_name }} is authorized to issue tickets for, and set their status:</p>
                    
                    <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                        @foreach($allRoutes as $route)
                            @php $isAssigned = $assignedRoutes->has($route->id); $status = $isAssigned ? $assignedRoutes->get($route->id)->pivot->status : 'active'; @endphp
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between p-2.5 rounded-xl border {{ $isAssigned ? 'bg-brand-50/50 border-brand-200' : 'bg-white border-slate-200' }} transition text-xs gap-2">
                                <label class="flex items-center gap-2.5 cursor-pointer flex-1">
                                    <input type="hidden" name="routes[{{ $route->id }}][selected]" value="0">
                                    <input type="checkbox" name="routes[{{ $route->id }}][selected]" value="1" {{ $isAssigned ? 'checked' : '' }}
                                        class="rounded border-slate-300 text-brand-600 focus:ring-brand-500 h-4 w-4">
                                    <span class="font-bold text-slate-800">{{ $route->name }}</span>
                                </label>
                                <select name="routes[{{ $route->id }}][status]" class="text-xs border-slate-300 rounded-lg py-1 px-2 focus:ring-brand-500">
                                    <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="suspended" {{ $status === 'suspended' ? 'selected' : '' }}>Suspended</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" class="px-4 py-2 rounded-xl bg-brand-600 text-white font-bold text-xs hover:bg-brand-700 transition shadow flex items-center gap-1.5">
                            <i class="fas fa-save"></i> Save Authorization
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="lg:col-span-2">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 pb-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-900 flex items-center gap-2">
                            <i class="fas fa-history text-brand-600"></i> Financial Ledger & Float History
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">Centralized `system_transactions` records for {{ $agent->agency_name }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50/80">
                            <tr>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Tx Number & Date</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Category / Type</th>
                                <th class="px-6 py-3.5 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Amount (TZS)</th>
                                <th class="px-6 py-3.5 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Float Balance After</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($transactions as $tx)
                                <tr class="hover:bg-slate-50/60 transition-colors text-sm">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="font-mono font-bold text-slate-800 block">{{ $tx->transaction_number }}</span>
                                        <span class="text-xs text-slate-400">{{ $tx->created_at->format('M d, Y H:i') }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @php
                                            $badgeClass = match($tx->category) {
                                                'float-top-up' => 'bg-emerald-50 text-emerald-700 border border-emerald-200',
                                                'ticket-sale'  => 'bg-blue-50 text-blue-700 border border-blue-200',
                                                'commission'   => 'bg-purple-50 text-purple-700 border border-purple-200',
                                                'refund'       => 'bg-amber-50 text-amber-700 border border-amber-200',
                                                'cancellation-penalty' => 'bg-rose-50 text-rose-700 border border-rose-200',
                                                default        => 'bg-slate-100 text-slate-700 border border-slate-200',
                                            };
                                        @endphp
                                        <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase {{ $badgeClass }}">
                                            {{ str_replace('-', ' ', $tx->category) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <p class="text-xs text-slate-700 font-medium max-w-sm truncate">{{ $tx->description ?? '-' }}</p>
                                        @if($tx->payment_reference)
                                            <span class="text-[11px] font-mono text-slate-400">Ref: {{ $tx->payment_reference }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-mono font-bold {{ in_array($tx->type, ['credit', 'reverse']) && $tx->category !== 'cancellation-penalty' ? 'text-emerald-600' : 'text-slate-800' }}">
                                        {{ in_array($tx->type, ['credit', 'reverse']) && $tx->category !== 'cancellation-penalty' ? '+' : '-' }}{{ number_format($tx->amount_tzs, 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right font-mono font-semibold text-slate-600">
                                        {{ $tx->balance_after ? number_format($tx->balance_after, 2) . ' TZS' : '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-slate-400 font-medium italic">
                                        No transactions recorded in the centralized ledger for this agency yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($transactions->hasPages())
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                        {{ $transactions->links() }}
                    </div>
                @endif
            </div>
        </div>

        <!-- Topup Modal (Rendered outside header z-40 inside main with z-[100]) -->
        @if($agent->account)
            <div x-show="showTopupModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showTopupModal" @click="showTopupModal = false" class="fixed inset-0 transition-opacity bg-slate-900/70 backdrop-blur-sm"></div>
                    <div x-show="showTopupModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 relative z-[101]">
                        <div class="bg-emerald-600 px-6 py-4 flex items-center justify-between text-white">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                <i class="fas fa-wallet"></i> Top-Up Float Balance
                            </h3>
                            <button type="button" @click="showTopupModal = false" class="text-emerald-100 hover:text-white transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form action="{{ route('admin.agents.topup', $agent) }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Top-Up Amount (TZS) *</label>
                                <input type="number" step="1000" min="1000" name="amount" required placeholder="e.g. 5000000"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition-all">
                                <p class="text-[11px] text-amber-600 font-semibold mt-1 flex items-center gap-1">
                                    <i class="fas fa-shield-alt"></i> Note: Top-ups > 5,000,000 TZS require Dual Control Maker-Checker approval.
                                </p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">Bank / Transaction Reference *</label>
                                <input type="text" name="payment_reference" required placeholder="e.g. CRDB-RECEIPT-98234"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 shadow-sm focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition-all">
                            </div>
                            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                                <button type="button" @click="showTopupModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs hover:bg-slate-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 text-white font-bold text-xs hover:bg-emerald-700 shadow transition">
                                    <i class="fas fa-check mr-1"></i> Process Top-Up
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Adjust Credit Limit Modal (Rendered outside header z-40 inside main with z-[100]) -->
            <div x-show="showLimitModal" class="fixed inset-0 z-[100] overflow-y-auto" style="display: none;">
                <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                    <div x-show="showLimitModal" @click="showLimitModal = false" class="fixed inset-0 transition-opacity bg-slate-900/70 backdrop-blur-sm"></div>
                    <div x-show="showLimitModal" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 relative z-[101]">
                        <div class="bg-slate-900 px-6 py-4 flex items-center justify-between text-white">
                            <h3 class="font-bold text-base flex items-center gap-2">
                                <i class="fas fa-sliders-h text-brand-400"></i> Adjust Credit Limit
                            </h3>
                            <button type="button" @click="showLimitModal = false" class="text-slate-400 hover:text-white transition">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                        <form action="{{ route('admin.agents.update-credit-limit', $agent) }}" method="POST" class="p-6 space-y-4">
                            @csrf
                            <div>
                                <label class="block text-xs font-bold text-slate-700 mb-1">New Credit Limit (TZS) *</label>
                                <input type="number" step="1000" min="0" name="credit_limit" value="{{ $agent->account->credit_limit }}" required
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 font-mono text-sm text-slate-900 shadow-sm focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20 focus:outline-none transition-all">
                                <p class="text-[11px] text-slate-400 mt-1">This sets the maximum overdraft allowance for ticketing before float top-up.</p>
                            </div>
                            <div class="pt-4 border-t border-slate-200 flex items-center justify-end gap-3">
                                <button type="button" @click="showLimitModal = false" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 font-semibold text-xs hover:bg-slate-200 transition">
                                    Cancel
                                </button>
                                <button type="submit" class="px-5 py-2 rounded-xl bg-slate-900 text-white font-bold text-xs hover:bg-slate-800 shadow transition">
                                    <i class="fas fa-save mr-1"></i> Update Limit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-layouts.admin>
