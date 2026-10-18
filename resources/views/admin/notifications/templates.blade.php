<x-layouts.admin>
    <x-slot:title>Notification Templates</x-slot:title>
    <x-slot:header>Communication Settings</x-slot:header>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Notification Templates</h2>
        <p class="text-slate-500 text-sm">Manage SMS, email, and in-app templates triggered by system events.</p>
    </div>

    @if (session('success'))
        <div class="mb-4 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3">
            <i class="fas fa-check-circle mt-1"></i><div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 border-b border-slate-200/60">
                    <tr>
                        <th class="px-6 py-4 font-semibold text-slate-700">Event</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Channel</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Locale</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">Subject (Email)</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($templates as $t)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ ucwords(str_replace('_', ' ', $t->event)) }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase
                                    {{ match($t->channel) { 'sms' => 'bg-blue-50 text-blue-700', 'email' => 'bg-purple-50 text-purple-700', default => 'bg-slate-100 text-slate-600' } }}">
                                    <i class="fas {{ match($t->channel) { 'sms' => 'fa-sms', 'email' => 'fa-envelope', default => 'fa-bell' } }} mr-1"></i> {{ $t->channel }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center"><span class="font-mono bg-slate-100 px-2 py-1 rounded-md text-xs">{{ strtoupper($t->locale) }}</span></td>
                            <td class="px-6 py-4 text-slate-600 truncate max-w-xs">{{ $t->subject ?? '—' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $t->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $t->is_active ? 'Active' : 'Disabled' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <x-dual-control.pending-badge subject="notification_template:{{ $t->event }}:{{ $t->channel }}:{{ $t->locale }}" class="mr-1" />
                                <form action="{{ route('admin.notifications.toggle-template', $t) }}" method="POST" class="inline">@csrf
                                    <button class="text-xs {{ $t->is_active ? 'text-slate-400 hover:text-slate-600' : 'text-blue-600 hover:text-blue-800' }}">{{ $t->is_active ? 'Disable' : 'Enable' }}</button>
                                </form>
                            </td>
                        </tr>
                        @if($t->body)
                            <tr class="bg-slate-50/30 border-b-2 border-slate-100"><td colspan="6" class="px-6 py-3 text-xs text-slate-500 whitespace-normal"><b>Template Body:</b> {{ $t->body }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-envelope-open-text text-3xl mb-3 block"></i>No templates configured.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($templates->hasPages())<div class="border-t border-slate-200/60 px-6 py-4">{{ $templates->links() }}</div>@endif
    </div>
</x-layouts.admin>
