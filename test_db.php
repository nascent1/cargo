<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$tickets = \App\Models\Ticket::orderBy('id', 'desc')->limit(10)->get();
foreach ($tickets as $t) {
    echo "ID: {$t->id}, Room: " . ($t->room_id ?? 'NULL') . ", Whole: " . ($t->is_whole_room ? 'YES' : 'NO') . "\n";
}
