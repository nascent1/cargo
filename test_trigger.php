<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$inv = \App\Models\SeatInventory::first();
echo "Before: sold: {$inv->sold_seats} held: {$inv->held_seats}\n";

$bl = new \App\Models\BookingLock();
$bl->voyage_id = $inv->voyage_id;
$bl->ferry_class_id = $inv->travel_class_id;
$bl->room_id = \App\Models\FerryClassRoom::where('ferry_class_id', $inv->travel_class_id)->first()->id ?? null;
if (!$bl->room_id) { echo "No room found\n"; exit; }
$bl->is_whole_room = true;
$bl->quantity = 1;
$bl->expires_at = now()->addMinutes(5);
$bl->session_id = 'test';
$bl->save();

$inv->refresh();
echo "After lock insert: sold: {$inv->sold_seats} held: {$inv->held_seats}\n";

$bl->delete();
$inv->refresh();
echo "After lock delete: sold: {$inv->sold_seats} held: {$inv->held_seats}\n";
