<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Lookup & Reference data
            LanguageSeeder::class,
            ReferenceDataSeeder::class,
            LookupStatusSeeder::class,

            // 2. RBAC (roles, permissions, mappings)
            RbacSeeder::class,

            // 3. System configuration & Menus
            SystemConfigSeeder::class,
            MenuSeeder::class,

            DualControlConfigSeeder::class,

            // 4. Admin users & System sales channels
            AdminUserSeeder::class,
            SystemSalesChannelsSeeder::class,
            AgentSeeder::class,

            // 5. Cargo domain data
            CargoSystemConfigSeeder::class,
            CargoTypeSeeder::class,
            CargoPermissionSeeder::class,
            CargoTransactionCategorySeeder::class,

            // 6. Notification templates
            NotificationTemplateSeeder::class,
        ]);
    }
}
