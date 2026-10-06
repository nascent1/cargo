<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PortSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ports = [
            ['name' => 'Zanzibar', 'code' => 'ZNZ', 'status' => 'active'],
            ['name' => 'Dar es Salaam', 'code' => 'DSM', 'status' => 'active'],
            ['name' => 'Wete', 'code' => 'WET', 'status' => 'active'],
            ['name' => 'Tanga', 'code' => 'TNG', 'status' => 'active'],
        ];

        foreach ($ports as $port) {
            \App\Models\Port::updateOrCreate(['code' => $port['code']], $port);
        }
    }
}
