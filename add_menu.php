<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$settings = App\Models\Menu::where('title', 'Settings')->first();
App\Models\Menu::firstOrCreate(
    ['route' => 'admin.settings.travel-classes.index'],
    [
        'title' => 'Travel Classes',
        'icon' => 'fas fa-couch',
        'parent_id' => $settings ? $settings->id : null,
        'sort_order' => 50,
        'permission_name' => 'setting:read',
        'is_active' => true
    ]
);
echo "Menu added!\n";
