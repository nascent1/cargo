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

class FerrySimulationSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ensure Ports exist
        $darPort = Port::firstOrCreate(['code' => 'DAR'], ['name' => 'Dar es Salaam Port', 'city' => 'Dar es Salaam', 'status' => 'active']);
        $znzPort = Port::firstOrCreate(['code' => 'ZNZ'], ['name' => 'Zanzibar Malindi Port', 'city' => 'Zanzibar City', 'status' => 'active']);
        $pemPort = Port::firstOrCreate(['code' => 'PEM'], ['name' => 'Mkoani Port Pemba', 'city' => 'Pemba', 'status' => 'active']);

        // 2. Ensure 4 Active Routes exist
        $routeDarZnz = Route::firstOrCreate(
            ['origin_port_id' => $darPort->id, 'destination_port_id' => $znzPort->id],
            ['name' => 'Dar es Salaam to Zanzibar Express', 'distance_nm' => 45.0, 'status' => 'active']
        );
        $routeZnzDar = Route::firstOrCreate(
            ['origin_port_id' => $znzPort->id, 'destination_port_id' => $darPort->id],
            ['name' => 'Zanzibar to Dar es Salaam Express', 'distance_nm' => 45.0, 'status' => 'active']
        );
        $routeZnzPem = Route::firstOrCreate(
            ['origin_port_id' => $znzPort->id, 'destination_port_id' => $pemPort->id],
            ['name' => 'Zanzibar to Pemba Express', 'distance_nm' => 75.0, 'status' => 'active']
        );
        $routePemZnz = Route::firstOrCreate(
            ['origin_port_id' => $pemPort->id, 'destination_port_id' => $znzPort->id],
            ['name' => 'Pemba to Zanzibar Express', 'distance_nm' => 75.0, 'status' => 'active']
        );

        // 3. Ensure Ferries exist (2 with layout, 1 without layout for open pool testing)
        $ferry1 = Ferry::firstOrCreate(
            ['name' => 'Zanzibar 1'],
            ['type' => 'Catamaran High Speed', 'port_of_registry' => 'Zanzibar Port', 'capacity' => 500, 'has_layout' => true, 'status' => 'active']
        );
        $ferry2 = Ferry::firstOrCreate(
            ['name' => 'Zanzibar 2'],
            ['type' => 'Catamaran High Speed', 'port_of_registry' => 'Zanzibar Port', 'capacity' => 650, 'has_layout' => true, 'status' => 'active']
        );
        $ferryOpen = Ferry::firstOrCreate(
            ['name' => 'Kilimanjaro IV'],
            ['type' => 'Catamaran High Speed', 'port_of_registry' => 'Zanzibar Port', 'capacity' => 400, 'has_layout' => false, 'status' => 'active']
        );

        $ferries = [$ferry1, $ferry2, $ferryOpen];

        // 4. Ensure 5 Classes for each ferry & seed Fares across all routes
        $classDefinitions = [
            ['name' => 'VIP Royal Class', 'capacity' => 40, 'sort_order' => 1, 'tzs' => 120000, 'usd' => 50, 'child_tzs' => 80000, 'child_usd' => 35],
            ['name' => 'Business Class', 'capacity' => 80, 'sort_order' => 2, 'tzs' => 80000, 'usd' => 35, 'child_tzs' => 50000, 'child_usd' => 25],
            ['name' => 'Gold Class', 'capacity' => 120, 'sort_order' => 3, 'tzs' => 60000, 'usd' => 25, 'child_tzs' => 40000, 'child_usd' => 18],
            ['name' => 'Silver Plus', 'capacity' => 160, 'sort_order' => 4, 'tzs' => 45000, 'usd' => 20, 'child_tzs' => 30000, 'child_usd' => 14],
            ['name' => 'Economy Class', 'capacity' => 250, 'sort_order' => 5, 'tzs' => 35000, 'usd' => 15, 'child_tzs' => 20000, 'child_usd' => 10],
        ];

        $routes = [$routeDarZnz, $routeZnzDar, $routeZnzPem, $routePemZnz];

        foreach ($ferries as $ferry) {
            foreach ($classDefinitions as $def) {
                $fClass = FerryClass::firstOrCreate(
                    ['ferry_id' => $ferry->id, 'name' => $def['name']],
                    ['capacity' => $def['capacity'], 'sort_order' => $def['sort_order'], 'status' => 'active']
                );

                foreach ($routes as $route) {
                    // For Pemba routes slightly higher price
                    $multiplier = ($route->id == $routeZnzPem->id || $route->id == $routePemZnz->id) ? 1.3 : 1.0;
                    Fare::firstOrCreate(
                        ['route_id' => $route->id, 'ferry_class_id' => $fClass->id, 'ferry_id' => $ferry->id],
                        [
                            'adult_price' => round($def['tzs'] * $multiplier, 2),
                            'child_price' => round($def['child_tzs'] * $multiplier, 2),
                            'adult_price_usd' => round($def['usd'] * $multiplier, 2),
                            'child_price_usd' => round($def['child_usd'] * $multiplier, 2),
                            'status' => 'active',
                        ]
                    );
                }
            }
        }

        // 5. Create Trips, Schedules & Daily Voyages for today()-2d to today()+7d
        $departureTimes = ['07:00:00', '09:30:00', '12:30:00', '16:30:00'];

        foreach ($routes as $index => $route) {
            foreach ($departureTimes as $tIdx => $time) {
                $trip = Trip::firstOrCreate(
                    ['route_id' => $route->id, 'departure_time' => $time],
                    ['arrival_time' => date('H:i:s', strtotime($time) + 5400), 'frequency_pattern' => 'Daily', 'status' => 'active']
                );

                $assignedFerry = $ferries[($index + $tIdx) % count($ferries)];

                $schedule = Schedule::firstOrCreate(
                    ['trip_id' => $trip->id, 'ferry_id' => $assignedFerry->id],
                    ['start_date' => now()->subMonths(2)->format('Y-m-d'), 'end_date' => now()->addYear()->format('Y-m-d'), 'status' => 'active']
                );

                for ($dayOffset = -5; $dayOffset <= 60; $dayOffset++) {
                    $depDate = now()->addDays($dayOffset)->format('Y-m-d');
                    Voyage::firstOrCreate(
                        [
                            'schedule_id' => $schedule->id,
                            'route_id' => $route->id,
                            'ferry_id' => $assignedFerry->id,
                            'departure_date' => $depDate,
                        ],
                        [
                            'departure_time' => $time,
                            'status' => 'scheduled',
                        ]
                    );
                }
            }
        }
    }
}
