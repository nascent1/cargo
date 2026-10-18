<x-layouts.admin>
    <x-slot:title>Announcements</x-slot:title>
    <x-slot:header>System Announcements</x-slot:header>

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Announcements</h2>
        <p class="text-slate-500 text-sm">Manage global banners, agent notices, and portal announcements.</p>
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
                        <th class="px-6 py-4 font-semibold text-slate-700">Title</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Type</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Audience</th>
                        <th class="px-6 py-4 font-semibold text-slate-700">Duration</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-center">Status</th>
                        <th class="px-6 py-4 font-semibold text-slate-700 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($announcements as $a)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4 font-bold text-slate-800">{{ $a->title }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold uppercase
                                    {{ match($a->type) { 'info' => 'bg-blue-50 text-blue-700', 'warning' => 'bg-amber-50 text-amber-700', 'danger' => 'bg-red-50 text-red-700', default => 'bg-slate-100 text-slate-600' } }}">
                                    {{ $a->type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded text-xs font-bold bg-slate-100 text-slate-700 uppercase">{{ $a->target_audience }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 text-xs">{{ $a->start_date?->format('d M') }} — {{ $a->end_date?->format('d M Y') }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-3 py-1 rounded-full text-xs font-bold {{ $a->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $a->is_active ? 'Active' : 'Hidden' }}</span>
                            </td>
                            <td class="px-6 py-4 text-right flex gap-2 justify-end">
                                <form action="{{ route('admin.notifications.toggle-announcement', $a) }}" method="POST">@csrf
                                    <button class="text-xs text-blue-600 hover:text-blue-800">{{ $a->is_active ? 'Hide' : 'Show' }}</button>
                                </form>
                                <form action="{{ route('admin.notifications.destroy-announcement', $a) }}" method="POST"
                                    data-confirm="This can't be undone." data-confirm-title="Delete “{{ $a->title }}”?" data-confirm-button="Delete" data-confirm-tone="danger">@csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @if($a->message)
                            <tr class="bg-slate-50/30 border-b-2 border-slate-100"><td colspan="6" class="px-6 py-3 text-xs text-slate-500 whitespace-normal"><b>Message:</b> {{ $a->message }}</td></tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="px-6 py-12 text-center text-slate-400"><i class="fas fa-bullhorn text-3xl mb-3 block"></i>No announcements.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($announcements->hasPages())<div class="border-t border-slate-200/60 px-6 py-4">{{ $announcements->links() }}</div>@endif
    </div>
</x-layouts.admin>
