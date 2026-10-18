<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cargo Receipt - {{ $consignment->consignment_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700;800&family=Outfit:wght@400;600;800;900&display=swap');
        body { font-family: 'Inter', sans-serif; }
        .font-mono-code { font-family: 'JetBrains Mono', monospace; }
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; margin: 0; padding: 0; background: #fff !important; }
            .no-print { display: none !important; }
            .receipt-card { box-shadow: none !important; border: 1px solid #000 !important; margin: 0 !important; width: 100% !important; max-width: none !important; }
            @page { size: 80mm auto; margin: 2mm; }
        }
        .barcode-h {
            background-image: repeating-linear-gradient(
                to right,
                #000 0px, #000 2px, transparent 2px, transparent 4px,
                #000 4px, #000 7px, transparent 7px, transparent 8px,
                #000 8px, #000 12px, transparent 12px, transparent 14px
            );
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-4 flex flex-col items-center min-h-screen" onload="window.print();">

    <!-- Print Toolbar -->
    <div class="no-print mb-6 p-4 bg-white rounded-2xl shadow-sm border border-slate-200/80 flex flex-wrap gap-3 w-full max-w-md justify-between items-center">
        <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm"><i class="fas fa-receipt"></i></div>
            <div>
                <span class="text-sm font-extrabold text-slate-800 block">Cargo Receipt — 80mm Thermal</span>
                <span class="text-xs text-slate-500">Master Waybill & Receipt</span>
            </div>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold rounded-xl text-xs shadow-md transition-all flex items-center gap-1.5">
                <span>Print Receipt</span>
            </button>
            <button onclick="window.close()" class="px-5 py-2.5 bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold rounded-xl text-xs transition-colors">
                <span>Close</span>
            </button>
        </div>
    </div>

    @php
        $vesselName = strtoupper($consignment->transportService?->transportUnit?->name ?? 'UNASSIGNED');
        $service = $consignment->transportService;
        $depDate = $service?->departure_date
            ? \Carbon\Carbon::parse(\Carbon\Carbon::parse($service->departure_date)->toDateString() . ' ' . ($service->departure_time ?? '00:00:00'))
            : null;
        $originPort = $consignment->route?->origin?->name ?? 'ORIGIN';
        $destPort = $consignment->route?->destination?->name ?? 'DESTINATION';
        $bookingRef = $consignment->consignment_number;
        $companyName = \App\Models\SystemConfig::getValue('company_name', config('app.name'));
        $footerText = \App\Models\SystemConfig::getValue('receipt_footer_text');
        $receiptNo = $consignment->receipts->sortByDesc('issued_at')->first()?->receipt_number ?? 'N/A';
        $totalPriceStr = number_format($consignment->billing_amount, 0) . ' ' . $consignment->billing_currency;
        $paymentLabel = $consignment->payment_method_label ?? 'Not recorded';
        $totalWeight = number_format((float) $consignment->total_weight_kg, 1);
        $totalVolume = number_format((float) $consignment->items->sum('volume_cbm'), 3);
    @endphp

    <!-- Consolidated Receipt Card -->
    <div class="receipt-card w-full max-w-[302px] bg-white border border-slate-300 rounded-xl shadow-md overflow-hidden">

        <!-- Header -->
        <div class="bg-slate-900 text-white px-4 py-3 text-center">
            <div class="text-xs font-extrabold tracking-widest uppercase">{{ $companyName }}</div>
            <div class="text-[10px] text-slate-400 mt-0.5 font-mono-code">MASTER WAYBILL & RECEIPT</div>
            <div class="text-[9px] text-slate-400 font-mono-code">Consignment: {{ $consignment->items->count() }} Item(s)</div>
        </div>

        <!-- Receipt Number Bar -->
        <div class="bg-slate-100 border-b border-slate-200 px-4 py-2 flex items-center justify-between">
            <div>
                <div class="text-[8px] font-bold text-slate-400 uppercase tracking-wider">Receipt No.</div>
                <div class="text-xs font-extrabold text-slate-900 font-mono-code">{{ $receiptNo }}</div>
            </div>
            <div class="text-right">
                <div class="text-[8px] font-bold text-slate-400 uppercase tracking-wider">Date</div>
                <div class="text-xs font-bold text-slate-900 font-mono-code">{{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>

        <!-- Route & Voyage -->
        <div class="px-4 py-3 border-b border-dashed border-slate-300">
            <div class="flex items-center justify-center gap-2 mb-2">
                <span class="text-xs font-black text-slate-800 uppercase">{{ $originPort }}</span>
                <span class="text-xs text-slate-400">→</span>
                <span class="text-xs font-black text-slate-800 uppercase">{{ $destPort }}</span>
            </div>
            <div class="grid grid-cols-3 gap-2 text-center">
                <div>
                    <div class="text-[8px] font-bold text-slate-400 uppercase">Vessel</div>
                    <div class="text-[10px] font-extrabold text-slate-800">{{ $vesselName }}</div>
                </div>
                <div>
                    <div class="text-[8px] font-bold text-slate-400 uppercase">Dep. Date</div>
                    <div class="text-[10px] font-extrabold text-slate-800 font-mono-code">{{ $depDate ? $depDate->format('d/m/Y') : 'TBD' }}</div>
                </div>
                <div>
                    <div class="text-[8px] font-bold text-slate-400 uppercase">Dep. Time</div>
                    <div class="text-[10px] font-extrabold text-slate-800 font-mono-code">{{ $depDate ? $depDate->format('H:i') : 'TBD' }}</div>
                </div>
            </div>
        </div>

        <!-- Sender & Receiver -->
        <div class="px-4 py-3 border-b border-dashed border-slate-300">
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <div class="text-[8px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">Sender / Mtumaji</div>
                    <div class="text-[10px] font-black text-slate-900 leading-tight">{{ strtoupper($consignment->sender_name ?? 'N/A') }}</div>
                    @if($consignment->sender_phone)
                    <div class="text-[9px] text-slate-600 font-mono-code mt-0.5">{{ $consignment->sender_phone }}</div>
                    @endif
                </div>
                <div>
                    <div class="text-[8px] font-extrabold text-slate-500 uppercase tracking-wider mb-1">Receiver / Mpokeaji</div>
                    <div class="text-[10px] font-black text-slate-900 leading-tight">{{ strtoupper($consignment->receiver_name ?? 'N/A') }}</div>
                    @if($consignment->receiver_phone)
                    <div class="text-[9px] text-slate-600 font-mono-code mt-0.5">{{ $consignment->receiver_phone }}</div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Cargo Items Summary Table -->
        <div class="px-3 py-3 border-b border-dashed border-slate-300">
            <div class="text-[8px] font-extrabold text-slate-500 uppercase tracking-widest mb-1.5">Items Summary / Muhtasari wa Mzigo</div>
            <table class="w-full text-left text-[9px]">
                <thead>
                    <tr class="border-b border-slate-200">
                        <th class="py-1 font-bold">Item</th>
                        <th class="py-1 font-bold">Wt/Vol</th>
                        <th class="py-1 font-bold text-right">Fare</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100/50">
                    @foreach($consignment->items as $item)
                    <tr class="border-b border-dashed border-slate-200 last:border-0">
                        <td class="py-1.5 align-top">
                            <div class="font-bold text-slate-900 uppercase truncate max-w-[100px]">{{ (int) $item->quantity > 1 ? $item->quantity . ' × ' : '' }}{{ $item->cargoType?->name ?? 'Cargo' }}</div>
                            @if($item->vehicle_reg_number)<div class="text-[8px] font-bold text-slate-700 font-mono-code">{{ $item->vehicle_reg_number }}</div>@endif
                            @if((float) $item->value_charge > 0)<div class="text-[7px] text-slate-600">Value {{ number_format((float) $item->declared_value, 0) }} · charge {{ number_format((float) $item->value_charge, 0) }}</div>@endif
                            <div class="text-[7px] text-slate-500 font-mono-code">{{ $item->qr_code_token ?? 'N/A' }}</div>
                        </td>
                        <td class="py-1.5 align-top font-mono-code text-slate-700">
                            <div>{{ number_format((float) $item->weight_kg, 1) }}kg</div>
                            <div>{{ number_format((float) $item->volume_cbm, 3) }}cbm</div>
                        </td>
                        <td class="py-1.5 align-top text-right font-mono-code font-bold text-slate-900">
                            {{ number_format((float) $item->final_fare, 0) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="mt-2 pt-2 border-t border-slate-200 flex justify-between items-center text-[9px] font-mono-code">
                <span class="font-bold text-slate-500">Totals:</span>
                <span class="font-bold">{{ $totalWeight }} kg | {{ $totalVolume }} cbm</span>
            </div>
        </div>

        <!-- Fare & Payment -->
        <div class="px-4 py-3 border-b border-dashed border-slate-300">
            <div class="text-[8px] font-extrabold text-slate-500 uppercase tracking-widest mb-2">Payment / Malipo</div>
            <div class="space-y-1">
                @php $receiptDiscount = (float) $consignment->discount_amount; @endphp
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-slate-500">Subtotal / Jumla Ndogo</span>
                    <span class="font-bold text-slate-700 font-mono-code">{{ $receiptDiscount > 0 ? number_format($consignment->billing_amount + $receiptDiscount, 0) . ' ' . $consignment->billing_currency : $totalPriceStr }}</span>
                </div>
                @php $receiptValueCharge = (float) $consignment->items->sum('value_charge'); @endphp
                @if($receiptValueCharge > 0)
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-slate-500">Incl. value charge / Ada ya Thamani</span>
                    <span class="font-bold text-slate-700 font-mono-code">{{ number_format($receiptValueCharge, 0) }} {{ $consignment->billing_currency }}</span>
                </div>
                @endif
                @if($receiptDiscount > 0)
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-slate-500">Discount / Punguzo</span>
                    <span class="font-bold text-slate-700 font-mono-code">−{{ number_format($receiptDiscount, 0) }} {{ $consignment->billing_currency }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-slate-500">Method / Njia ya Malipo</span>
                    <span class="font-bold text-slate-700 text-right">{{ $paymentLabel }}</span>
                </div>
                @if($consignment->payment_reference && $consignment->payment_method !== 'cash')
                <div class="flex justify-between items-center text-[10px]">
                    <span class="text-slate-500">Txn Ref / Kumbukumbu</span>
                    <span class="font-bold text-slate-700 font-mono-code text-right">{{ $consignment->payment_reference }}</span>
                </div>
                @endif
                <div class="flex justify-between items-center text-xs pt-1 border-t border-slate-200 mt-1">
                    <span class="font-extrabold text-slate-800 uppercase text-[10px]">Total Paid / Jumla Kamili</span>
                    <span class="font-black text-slate-900 font-mono-code text-sm">{{ $totalPriceStr }}</span>
                </div>
            </div>
        </div>

        <!-- QR Code & Barcode -->
        <div class="px-4 py-3 border-b border-dashed border-slate-300 text-center">
            <div class="inline-block bg-white border-2 border-slate-900 rounded-xl p-2 shadow-sm mb-2">
                <img src="{{ (new \chillerlan\QRCode\QRCode)->render($bookingRef) }}" alt="Consignment QR" class="w-24 h-24 object-contain" />
            </div>
            <div class="text-[8px] font-extrabold uppercase text-slate-500 tracking-wider">Scan Master Code / Thibitisha Mzigo Wote</div>
            <div class="text-[10px] font-mono-code font-extrabold text-slate-800 mt-0.5">{{ $bookingRef }}</div>
            <div class="w-full h-8 mt-2 overflow-hidden flex items-center justify-center">
                {!! (new \Picqer\Barcode\BarcodeGeneratorSVG())->getBarcode($bookingRef, \Picqer\Barcode\BarcodeGenerator::TYPE_CODE_128, 1.2, 28) !!}
            </div>
        </div>

        <!-- Audit Footer -->
        <div class="px-4 py-3 bg-slate-50">
            <div class="text-[8px] font-mono-code space-y-0.5 text-slate-600">
                <div><span class="font-bold">Booking Ref:</span> {{ $bookingRef }}</div>
                <div><span class="font-bold">Booked On:</span> {{ $consignment->created_at?->format('d/m/Y H:i') }}</div>
                <div><span class="font-bold">Booked By:</span> {{ $consignment->bookedByUser?->full_name ?? 'System' }}</div>
                <div><span class="font-bold">Printed By:</span> {{ auth()->user()?->full_name ?? 'System' }}</div>
                <div><span class="font-bold">Printed On:</span> {{ now()->format('d/m/Y H:i:s') }}</div>
            </div>
            <div class="mt-2 pt-2 border-t border-slate-200 text-center">
                <div class="text-[8px] text-slate-400 font-bold uppercase tracking-wider">{{ $companyName }} — Cargo Division</div>
                @if($footerText)
                <div class="text-[7px] text-slate-500 mt-0.5 font-bold">{{ $footerText }}</div>
                @endif
                <div class="text-[7px] text-slate-400 mt-0.5">This receipt is proof of cargo acceptance. Present on collection.</div>
                <div class="text-[7px] text-slate-400">Stakabadhi hii ni uthibitisho wa kupokea mzigo. Ionyeshe wakati wa kuchukua.</div>
            </div>
        </div>
    </div>

</body>
</html>
