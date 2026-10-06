<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\Port;
use App\Models\Route;
use App\Models\Trip;
use App\Models\Schedule;
use App\Models\Voyage;
use App\Models\Fare;

class FerryOperationsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // 1. Create Ferries
        $ferry1 = Ferry::firstOrCreate(
            ['name' => 'Kilimanjaro VII'],
            [
                'type' => 'Catamaran High Speed',
                'port_of_registry' => 'Zanzibar Port',
                'capacity' => 500,
                'has_layout' => true,
                'status' => 'active',
            ]
        );

        $ferry2 = Ferry::firstOrCreate(
            ['name' => 'Kilimanjaro VIII'],
            [
                'type' => 'Catamaran High Speed',
                'port_of_registry' => 'Zanzibar Port',
                'capacity' => 650,
                'has_layout' => true,
                'status' => 'active',
            ]
        );

        // 2. Create Ferry Classes (linked to each ferry)
        $vipClass1 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry1->id, 'name' => 'VIP Royal Class'],
            ['capacity' => 50, 'sort_order' => 1, 'status' => 'active']
        );
        $businessClass1 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry1->id, 'name' => 'Business Class'],
            ['capacity' => 150, 'sort_order' => 2, 'status' => 'active']
        );
        $economyClass1 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry1->id, 'name' => 'Economy Class'],
            ['capacity' => 300, 'sort_order' => 3, 'status' => 'active']
        );

        $vipClass2 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry2->id, 'name' => 'VIP Royal Class'],
            ['capacity' => 80, 'sort_order' => 1, 'status' => 'active']
        );
        $businessClass2 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry2->id, 'name' => 'Business Class'],
            ['capacity' => 200, 'sort_order' => 2, 'status' => 'active']
        );
        $economyClass2 = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry2->id, 'name' => 'Economy Class'],
            ['capacity' => 370, 'sort_order' => 3, 'status' => 'active']
        );

        // 3. Create Ports
        $darPort = Port::firstOrCreate(['code' => 'DAR'], ['name' => 'Dar es Salaam Port', 'city' => 'Dar es Salaam', 'status' => 'active']);
        $znzPort = Port::firstOrCreate(['code' => 'ZNZ'], ['name' => 'Zanzibar Malindi Port', 'city' => 'Zanzibar City', 'status' => 'active']);
        $tgaPort = Port::firstOrCreate(['code' => 'TGA'], ['name' => 'Tanga Port', 'city' => 'Tanga', 'status' => 'active']);
        $pemPort = Port::firstOrCreate(['code' => 'PEM'], ['name' => 'Mkoani Port Pemba', 'city' => 'Pemba', 'status' => 'active']);

        // 4. Create Routes
        $routeDarZnz = Route::firstOrCreate(
            ['origin_port_id' => $darPort->id, 'destination_port_id' => $znzPort->id],
            [
                'name' => 'Dar es Salaam to Zanzibar Express',
                'distance_nm' => 45.0,
                'status' => 'active',
            ]
        );

        $routeZnzDar = Route::firstOrCreate(
            ['origin_port_id' => $znzPort->id, 'destination_port_id' => $darPort->id],
            [
                'name' => 'Zanzibar to Dar es Salaam Express',
                'distance_nm' => 45.0,
                'status' => 'active',
            ]
        );

        // 5. Create Fares for Routes and Classes
        Fare::firstOrCreate(
            ['route_id' => $routeDarZnz->id, 'ferry_class_id' => $vipClass1->id],
            ['ferry_id' => $ferry1->id, 'adult_price' => 120000.00, 'child_price' => 80000.00, 'adult_price_usd' => 50.00, 'child_price_usd' => 35.00, 'status' => 'active']
        );
        Fare::firstOrCreate(
            ['route_id' => $routeDarZnz->id, 'ferry_class_id' => $businessClass1->id],
            ['ferry_id' => $ferry1->id, 'adult_price' => 80000.00, 'child_price' => 50000.00, 'adult_price_usd' => 35.00, 'child_price_usd' => 25.00, 'status' => 'active']
        );
        Fare::firstOrCreate(
            ['route_id' => $routeDarZnz->id, 'ferry_class_id' => $economyClass1->id],
            ['ferry_id' => $ferry1->id, 'adult_price' => 50000.00, 'child_price' => 30000.00, 'adult_price_usd' => 20.00, 'child_price_usd' => 15.00, 'status' => 'active']
        );

        // 6. Create Trips and Schedules
        $trip1 = Trip::firstOrCreate(
            ['route_id' => $routeDarZnz->id, 'departure_time' => '07:00:00'],
            ['arrival_time' => '08:30:00', 'frequency_pattern' => 'Daily', 'status' => 'active']
        );

        $schedule1 = Schedule::firstOrCreate(
            ['trip_id' => $trip1->id, 'ferry_id' => $ferry1->id],
            [
                'start_date' => now()->subMonth()->format('Y-m-d'),
                'end_date' => now()->addYear()->format('Y-m-d'),
                'status' => 'active',
            ]
        );

        // 7. Create upcoming Voyages for testing (up to 60 days ahead)
        foreach (range(-5, 60) as $dayOffset) {
            $depDate = now()->addDays($dayOffset)->format('Y-m-d');
            Voyage::firstOrCreate(
                [
                    'schedule_id' => $schedule1->id,
                    'route_id' => $routeDarZnz->id,
                    'ferry_id' => $ferry1->id,
                    'departure_date' => $depDate,
                ],
                [
                    'departure_time' => '07:00:00',
                    'status' => 'scheduled',
                ]
            );

            Voyage::firstOrCreate(
                [
                    'schedule_id' => $schedule1->id,
                    'route_id' => $routeDarZnz->id,
                    'ferry_id' => $ferry2->id,
                    'departure_date' => $depDate,
                ],
                [
                    'departure_time' => '12:30:00',
                    'status' => 'scheduled',
                ]
            );
        }
    }
}
