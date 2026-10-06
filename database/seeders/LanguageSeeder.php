<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $languages = [
            ['code' => 'en', 'name' => 'English', 'is_active' => true],
            ['code' => 'sw', 'name' => 'Swahili', 'is_active' => true],
            ['code' => 'fr', 'name' => 'French', 'is_active' => true],
            ['code' => 'ar', 'name' => 'Arabic', 'is_active' => true],
        ];

        foreach ($languages as $lang) {
            \App\Models\Language::updateOrCreate(
                ['code' => $lang['code']],
                ['name' => $lang['name'], 'is_active' => $lang['is_active']]
            );
        }
    }
}
