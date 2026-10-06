<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Station;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\TransportService;
use App\Models\CargoInventory;
use App\Models\CargoFare;
use App\Models\CargoType;

class DemoCargoSeeder extends Seeder
{
    public function run()
    {
        // 1. Stations
        $dar = Station::firstOrCreate(
            ['code' => 'DAR'],
            ['name' => 'Dar es Salaam Port', 'city' => 'Dar es Salaam', 'status' => 'active', 'station_type' => 'port']
        );
        $znz = Station::firstOrCreate(
            ['code' => 'ZNZ'],
            ['name' => 'Zanzibar Port', 'city' => 'Zanzibar', 'status' => 'active', 'station_type' => 'port']
        );
        $maf = Station::firstOrCreate(
            ['code' => 'MAF'],
            ['name' => 'Mafia Port', 'city' => 'Mafia', 'status' => 'active', 'station_type' => 'port']
        );

        // 2. Routes
        $routeDarZnz = Route::firstOrCreate(
            ['code' => 'DAR-ZNZ'],
            ['name' => 'Dar es Salaam to Zanzibar', 'origin_port_id' => $dar->id, 'destination_port_id' => $znz->id, 'status' => 'active']
        );
        $routeZnzDar = Route::firstOrCreate(
            ['code' => 'ZNZ-DAR'],
            ['name' => 'Zanzibar to Dar es Salaam', 'origin_port_id' => $znz->id, 'destination_port_id' => $dar->id, 'status' => 'active']
        );
        $routeDarMaf = Route::firstOrCreate(
            ['code' => 'DAR-MAF'],
            ['name' => 'Dar es Salaam to Mafia', 'origin_port_id' => $dar->id, 'destination_port_id' => $maf->id, 'status' => 'active']
        );

        // 3. Transport Units
        $ferry1 = TransportUnit::firstOrCreate(
            ['registration_number' => 'FERRY-001'],
            [
                'name' => 'Kilimanjaro I', 
                'type' => 'Ferry',
                'transport_type' => 'ferry', 
                'has_cargo_deck' => true, 
                'status' => 'active'
            ]
        );
        
        $ferry2 = TransportUnit::firstOrCreate(
            ['registration_number' => 'FERRY-002'],
            [
                'name' => 'Mafia Express', 
                'type' => 'Ferry', 
                'transport_type' => 'ferry', 
                'has_cargo_deck' => true, 
                'status' => 'active'
            ]
        );

        // 3.5 Trips and Schedules
        $trip1Id = \Illuminate\Support\Facades\DB::table('trips')->insertGetId([
            'route_id' => $routeDarZnz->id, 
            'departure_time' => '07:00:00',
            'arrival_time' => '09:00:00', 
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $trip2Id = \Illuminate\Support\Facades\DB::table('trips')->insertGetId([
            'route_id' => $routeDarMaf->id, 
            'departure_time' => '09:00:00',
            'arrival_time' => '12:00:00', 
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $sched1 = \App\Models\Schedule::firstOrCreate(
            ['trip_id' => $trip1Id, 'ferry_id' => $ferry1->id],
            ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->addMonths(6)->format('Y-m-d'), 'status' => 'active']
        );
        $sched2 = \App\Models\Schedule::firstOrCreate(
            ['trip_id' => $trip2Id, 'ferry_id' => $ferry2->id],
            ['start_date' => now()->format('Y-m-d'), 'end_date' => now()->addMonths(6)->format('Y-m-d'), 'status' => 'active']
        );

        // 4. Transport Services & Inventories
        for ($i = 0; $i < 5; $i++) {
            $date = now()->addDays($i)->format('Y-m-d');
            
            // DAR-ZNZ Service
            $svc1 = TransportService::firstOrCreate(
                [
                    'route_id' => $routeDarZnz->id,
                    'transport_unit_id' => $ferry1->id,
                    'departure_date' => $date,
                ],
                [
                    'schedule_id' => $sched1->id,
                    'service_number' => 'SVC-DAR-ZNZ-' . now()->addDays($i)->format('md'),
                    'departure_time' => '07:00:00',
                    'transport_type' => 'ferry',
                    'status' => 'scheduled'
                ]
            );

            CargoInventory::firstOrCreate(
                ['transport_service_id' => $svc1->id],
                [
                    'transport_unit_id' => $ferry1->id,
                    'total_weight_capacity_kg' => 50000,
                    'used_weight_kg' => 0,
                    'total_volume_capacity_cbm' => 200,
                    'used_volume_cbm' => 0
                ]
            );
            
            // DAR-MAF Service
            $svc2 = TransportService::firstOrCreate(
                [
                    'route_id' => $routeDarMaf->id,
                    'transport_unit_id' => $ferry2->id,
                    'departure_date' => $date,
                ],
                [
                    'schedule_id' => $sched2->id,
                    'service_number' => 'SVC-DAR-MAF-' . now()->addDays($i)->format('md'),
                    'departure_time' => '09:00:00',
                    'transport_type' => 'ferry',
                    'status' => 'scheduled'
                ]
            );

            CargoInventory::firstOrCreate(
                ['transport_service_id' => $svc2->id],
                [
                    'transport_unit_id' => $ferry2->id,
                    'total_weight_capacity_kg' => 20000,
                    'used_weight_kg' => 0,
                    'total_volume_capacity_cbm' => 100,
                    'used_volume_cbm' => 0
                ]
            );
        }

        // 5. Cargo Fares
        $types = CargoType::all();
        foreach ($types as $type) {
            CargoFare::firstOrCreate(
                [
                    'route_id' => $routeDarZnz->id,
                    'cargo_type_id' => $type->id,
                ],
                [
                    'price_tzs' => 50000,
                    'price_usd' => 20,
                    'status' => 'active'
                ]
            );
            
            CargoFare::firstOrCreate(
                [
                    'route_id' => $routeDarMaf->id,
                    'cargo_type_id' => $type->id,
                ],
                [
                    'price_tzs' => 60000,
                    'price_usd' => 25,
                    'status' => 'active'
                ]
            );
        }
    }
}
