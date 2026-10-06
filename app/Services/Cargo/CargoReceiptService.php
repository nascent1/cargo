<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\CargoReceipt;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Cache;

class CargoReceiptService
{
    /**
     * Generate receipt text optimized for 80mm thermal printers.
     * Generally 42-48 characters per line.
     */
    public function generateReceipt(Consignment $consignment, int $userId): array
    {
        $receipt = CargoReceipt::create([
            'consignment_id' => $consignment->id,
            'receipt_number' => $this->getConfig('cargo_receipt_prefix', 'CR') . '-' . $consignment->consignment_number . '-' . time(),
            'amount_paid_tzs' => $consignment->total_amount_tzs,
            'amount_paid_usd' => $consignment->total_amount_usd,
            'issued_by_user_id' => $userId,
            'issued_at' => now(),
            'metadata' => [],
        ]);

        $currency = $consignment->total_amount_usd > 0 ? 'USD' : 'TZS';
        $amount = $currency === 'USD' ? $consignment->total_amount_usd : $consignment->total_amount_tzs;

        $lines = [];
        $lines[] = str_pad("UCLP CARGO SERVICES", 42, " ", STR_PAD_BOTH);
        $lines[] = str_pad("------------------------------------------", 42, " ");
        $lines[] = "Receipt No: " . $receipt->receipt_number;
        $lines[] = "Consignment: " . $consignment->consignment_number;
        $lines[] = "Date: " . $receipt->issued_at->format('Y-m-d H:i');
        $lines[] = "Cashier ID: " . $userId;
        $lines[] = str_pad("------------------------------------------", 42, " ");
        
        $lines[] = "Sender: " . substr($consignment->sender_name, 0, 34);
        $lines[] = "Receiver: " . substr($consignment->receiver_name, 0, 32);
        
        $lines[] = str_pad("------------------------------------------", 42, " ");
        $lines[] = str_pad("Item", 20) . str_pad("Qty/Wt", 10) . str_pad("Amount", 12, " ", STR_PAD_LEFT);
        
        foreach ($consignment->items as $item) {
            $name = substr($item->cargoType->name ?? 'Cargo', 0, 18);
            $qty = $item->weight_kg > 0 ? $item->weight_kg . 'kg' : '1';
            $itemAmt = number_format($item->final_fare, 2);
            $lines[] = str_pad($name, 20) . str_pad($qty, 10) . str_pad($itemAmt, 12, " ", STR_PAD_LEFT);
        }

        $lines[] = str_pad("------------------------------------------", 42, " ");
        $lines[] = str_pad("TOTAL ($currency):", 30) . str_pad(number_format($amount, 2), 12, " ", STR_PAD_LEFT);
        $lines[] = str_pad("------------------------------------------", 42, " ");

        $footer = $this->getConfig('receipt_footer_text', 'Thank you for using UCLP Cargo!');
        $lines[] = str_pad(substr($footer, 0, 42), 42, " ", STR_PAD_BOTH);

        return [
            'receipt_id' => $receipt->id,
            'receipt_number' => $receipt->receipt_number,
            'text_lines' => $lines,
            'qr_data' => $this->getConfig('tracking_qr_base_url', 'http://127.0.0.1/track') . '/' . $consignment->consignment_number
        ];
    }

    private function getConfig(string $key, string $default): string
    {
        return Cache::remember("sys_cfg_{$key}", 3600, function() use ($key, $default) {
            $cfg = SystemConfig::where('key_name', $key)->first();
            return $cfg ? $cfg->value : $default;
        });
    }
}
