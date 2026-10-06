<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // VEHICLES
            ['name' => 'Sedan / Saloon Car',       'code' => 'VEH-SEDAN',    'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 4.5,  'max_weight_kg' => 2500,  'icon' => '🚗', 'sort_order' => 1],
            ['name' => 'SUV / 4x4',                'code' => 'VEH-SUV',      'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 5.0,  'max_weight_kg' => 3500,  'icon' => '🚙', 'sort_order' => 2],
            ['name' => 'Pickup / Light Truck',      'code' => 'VEH-PICKUP',   'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 5.5,  'max_weight_kg' => 4000,  'icon' => '🛻', 'sort_order' => 3],
            ['name' => 'Minibus / Van',             'code' => 'VEH-MINIBUS',  'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 6.0,  'max_weight_kg' => 5000,  'icon' => '🚐', 'sort_order' => 4],
            ['name' => 'Bus / Coach',               'code' => 'VEH-BUS',      'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 12.0, 'max_weight_kg' => 18000, 'icon' => '🚌', 'sort_order' => 5],
            ['name' => 'Heavy Truck / Lorry',       'code' => 'VEH-TRUCK',    'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 16.5, 'max_weight_kg' => 30000, 'icon' => '🚛', 'sort_order' => 6],
            ['name' => 'Motorcycle',                'code' => 'VEH-MOTO',     'category' => 'vehicle',     'measurement_unit' => 'lane_meter', 'lane_meters' => 2.0,  'max_weight_kg' => 300,   'icon' => '🏍️', 'sort_order' => 7],
            ['name' => 'Bicycle',                   'code' => 'VEH-BICYCLE',  'category' => 'vehicle',     'measurement_unit' => 'unit',       'lane_meters' => 0,    'max_weight_kg' => 25,    'icon' => '🚲', 'sort_order' => 8],

            // FREIGHT
            ['name' => 'General Freight',           'code' => 'FRT-GENERAL',  'category' => 'freight',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 5000, 'icon' => '📦', 'sort_order' => 10],
            ['name' => 'Building Materials',        'code' => 'FRT-BUILDING', 'category' => 'freight',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 10000, 'icon' => '🧱', 'sort_order' => 11],
            ['name' => 'Agricultural Produce',      'code' => 'FRT-AGRI',     'category' => 'freight',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 5000, 'icon' => '🌾', 'sort_order' => 12],
            ['name' => 'Machinery / Equipment',     'code' => 'FRT-MACHINE',  'category' => 'freight',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 8000, 'icon' => '⚙️', 'sort_order' => 13],

            // PARCELS
            ['name' => 'Small Parcel (< 5kg)',      'code' => 'PCL-SMALL',    'category' => 'parcel',      'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 5,    'icon' => '📮', 'sort_order' => 20],
            ['name' => 'Medium Parcel (5-30kg)',    'code' => 'PCL-MEDIUM',   'category' => 'parcel',      'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 30,   'icon' => '📦', 'sort_order' => 21],
            ['name' => 'Large Parcel (30-100kg)',   'code' => 'PCL-LARGE',    'category' => 'parcel',      'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 100,  'icon' => '🗃️', 'sort_order' => 22],
            ['name' => 'Documents / Envelope',      'code' => 'PCL-DOCS',     'category' => 'parcel',      'measurement_unit' => 'unit','lane_meters' => 0, 'max_weight_kg' => 2,    'icon' => '✉️', 'sort_order' => 23],

            // ELECTRONICS
            ['name' => 'TV / Screen',               'code' => 'ELC-TV',       'category' => 'electronics', 'measurement_unit' => 'cbm', 'lane_meters' => 0, 'max_weight_kg' => 50,  'is_fragile' => true, 'icon' => '📺', 'sort_order' => 30],
            ['name' => 'Computer / Laptop',         'code' => 'ELC-PC',       'category' => 'electronics', 'measurement_unit' => 'cbm', 'lane_meters' => 0, 'max_weight_kg' => 30,  'is_fragile' => true, 'icon' => '💻', 'sort_order' => 31],
            ['name' => 'Home Appliance',            'code' => 'ELC-APPLIANCE','category' => 'electronics', 'measurement_unit' => 'cbm', 'lane_meters' => 0, 'max_weight_kg' => 100, 'is_fragile' => true, 'icon' => '🏠', 'sort_order' => 32],
            ['name' => 'Furniture',                 'code' => 'ELC-FURNITURE','category' => 'electronics', 'measurement_unit' => 'cbm', 'lane_meters' => 0, 'max_weight_kg' => 200, 'icon' => '🪑', 'sort_order' => 33],

            // LIVESTOCK
            ['name' => 'Cattle',                    'code' => 'LST-CATTLE',   'category' => 'livestock',   'measurement_unit' => 'unit','lane_meters' => 3.0, 'max_weight_kg' => 800, 'icon' => '🐄', 'sort_order' => 40],
            ['name' => 'Goat / Sheep',              'code' => 'LST-GOAT',     'category' => 'livestock',   'measurement_unit' => 'unit','lane_meters' => 1.0, 'max_weight_kg' => 100, 'icon' => '🐐', 'sort_order' => 41],

            // SPECIAL
            ['name' => 'Hazardous Materials',       'code' => 'SPL-HAZMAT',   'category' => 'special',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 1000, 'requires_hazmat_declaration' => true, 'icon' => '☢️', 'sort_order' => 50],
            ['name' => 'Perishable Goods',          'code' => 'SPL-PERISH',   'category' => 'special',     'measurement_unit' => 'kg',  'lane_meters' => 0, 'max_weight_kg' => 2000, 'icon' => '🧊', 'sort_order' => 51],
        ];

        foreach ($types as $type) {
            DB::table('cargo_types')->updateOrInsert(
                ['code' => $type['code']],
                array_merge([
                    'requires_hazmat_declaration' => false,
                    'is_fragile' => false,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $type)
            );
        }
    }
}
