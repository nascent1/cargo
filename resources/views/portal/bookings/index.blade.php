<x-layouts.iframe>
    <x-slot:title>Booking Management</x-slot:title>
    <x-slot:header>Reservations & Tickets</x-slot:header>
    <div class="mb-6 flex flex-wrap justify-between items-end gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Reservation & Ticket Dashboard</h2>
            <p class="text-slate-500 text-sm">Track real-time bookings, holds, and passenger tickets across all channels.</p>
        </div>
        <form method="GET" action="{{ route('portal.bookings.index') }}" class="flex flex-wrap items-center gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search ref or ticket..." class="px-4 py-2 rounded-xl border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-700">
            <select name="status" class="px-4 py-2 rounded-xl border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-700">
                <option value="">All Statuses</option>
                <option value="held" {{ request('status') == 'held' ? 'selected' : '' }}>Held</option>
                <option value="pending_payment" {{ request('status') == 'pending_payment' ? 'selected' : '' }}>Pending Payment</option>
                <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
            </select>
            <select name="source" class="px-4 py-2 rounded-xl border border-slate-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 text-sm font-semibold text-slate-700">
                <option value="">All Sources</option>
                <option value="web" {{ request('source') == 'web' ? 'selected' : '' }}>Web</option>
                <option value="app" {{ request('source') == 'app' ? 'selected' : '' }}>Mobile App</option>
                <option value="office" {{ request('source') == 'office' ? 'selected' : '' }}>Office / Staff</option>
                <option value="agent" {{ request('source') == 'agent' ? 'selected' : '' }}>Agent</option>
                <option value="pos" {{ request('source') == 'pos' ? 'selected' : '' }}>POS Terminal</option>
                <option value="api" {{ request('source') == 'api' ? 'selected' : '' }}>API Integration</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-xl shadow-sm transition-colors">
                Filter
            </button>
            @if(request()->hasAny(['search', 'status', 'source', 'date']))
                <a href="{{ route('portal.bookings.index') }}" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold rounded-xl transition-colors">
                    Reset
                </a>
            @endif
        </form>
    </div>

    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl flex items-start gap-3 animate-fade-in-up">
            <i class="fas fa-check-circle mt-1"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
        @if($bookings->isEmpty())
            <div class="p-12 text-center flex flex-col items-center">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mb-4 border border-slate-100 shadow-sm">
                    <i class="fas fa-ticket-alt text-2xl text-slate-400"></i>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No Reservations Found</h3>
                <p class="text-slate-500 text-sm mt-1 max-w-sm mx-auto">Try adjusting your filters or search terms.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/80 border-b border-slate-200/60">
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Reference</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Primary Passenger</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Source / Agent</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Amount</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Status</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider">Created</th>
                            <th class="py-4 px-6 text-xs font-bold text-slate-500 uppercase tracking-wider text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($bookings as $booking)
                            @php
                                $statusColors = [
                                    'held' => 'bg-amber-100 text-amber-800 border-amber-200',
                                    'pending_payment' => 'bg-blue-100 text-blue-800 border-blue-200',
                                    'confirmed' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                                    'cancelled' => 'bg-slate-100 text-slate-700 border-slate-200',
                                    'expired' => 'bg-rose-100 text-rose-800 border-rose-200',
                                ];
                                $badgeClass = $statusColors[$booking->status] ?? 'bg-slate-100 text-slate-700 border-slate-200';
                            @endphp
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-bold text-brand-600 font-mono">{{ $booking->booking_reference }}</div>
                                    <div class="text-xs text-slate-400">{{ $booking->items->count() }} ticket(s)</div>
                                </td>
                                <td class="py-4 px-6">
                                    @php $firstItem = $booking->items->first(); @endphp
                                    <div class="font-bold text-slate-800">{{ $firstItem?->passenger?->full_name ?? 'Guest' }}</div>
                                    <div class="text-xs text-slate-500">{{ $firstItem?->passenger?->phone ?? $booking->user?->phone ?? 'No contact' }}</div>
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-700 uppercase">
                                        {{ $booking->booking_source }}
                                    </span>
                                    @if($booking->bookedBy)
                                        <div class="text-xs text-slate-500 mt-1 font-semibold">By: {{ $booking->bookedBy->first_name }} {{ $booking->bookedBy->last_name }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <div class="font-bold text-slate-800">{{ $booking->currency }} {{ number_format($booking->net_amount, 2) }}</div>
                                    @if($booking->discount_amount > 0)
                                        <div class="text-xs text-emerald-600">Disc: -{{ number_format($booking->discount_amount, 2) }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-6">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $badgeClass }} uppercase tracking-wider">
                                        {{ $booking->status }}
                                    </span>
                                    @if($booking->status == 'held' && $booking->expires_at)
                                        <div class="text-xs text-amber-600 mt-1 font-medium">Expires: {{ $booking->expires_at->format('H:i:s') }}</div>
                                    @endif
                                </td>
                                <td class="py-4 px-6 text-sm text-slate-500 font-medium">
                                    {{ $booking->created_at->format('M d, H:i') }}
                                </td>
                                <td class="py-4 px-6 text-right">
                                    <a href="{{ route('portal.bookings.show', $booking) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-colors">
                                        <i class="fas fa-eye mr-1.5"></i> View
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="p-4 border-t border-slate-100">
                {{ $bookings->links() }}
            </div>
        @endif
    </div>
</x-layouts.iframe>
