<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Models\CargoFare;
use App\Models\CargoItem;
use App\Models\CargoType;
use App\Models\DualControlRequest;
use App\Models\Route;
use App\Services\Cargo\CargoFareService;
use Illuminate\Support\Facades\DB;

/**
 * Cargo type profile: its prices on every route, the full fare history, recent bookings and changes.
 */
class CargoTypeProfileController extends Controller
{
    public function show(CargoType $cargoType, CargoFareService $fareService)
    {
        $routes = Route::where('status', 'active')->orderBy('name')->get(['id', 'name', 'code']);

        $versions = CargoFare::withTrashed()->with(['route:id,name,code', 'transportUnit:id,name', 'creator:id,first_name,last_name,email',
                'ender:id,first_name,last_name,email', 'dualControlRequest:id,maker_id,checker_id,commence_at,executed_at', 'dualControlRequest.checker:id,first_name,last_name,email'])
            ->where('cargo_type_id', $cargoType->id)
            ->orderBy('route_id')->orderByRaw('transport_unit_id NULLS FIRST')->orderByDesc('effective_from')->orderByDesc('id')
            ->get();

        $usage = CargoItem::whereIn('fare_id', $versions->pluck('id'))->where('status', '!=', 'cancelled')
            ->groupBy('fare_id')->select('fare_id', DB::raw('count(*) as lines'), DB::raw('sum(final_fare) as revenue'))
            ->get()->keyBy('fare_id');

        $label = fn (CargoFare $f) => $fareService->priceLabel($cargoType, $f->prices());
        $live = $versions->filter(fn ($f) => !$f->trashed() && in_array($f->state, ['current', 'scheduled'], true));

        // One card per active route: the price now, the next change, vessel-specific prices
        $priceCards = $routes->map(fn (Route $r) => [
            'route' => $r,
            'current' => $live->first(fn ($f) => $f->route_id === $r->id && !$f->transport_unit_id && $f->state === 'current'),
            'next' => $live->filter(fn ($f) => $f->route_id === $r->id && !$f->transport_unit_id && $f->state === 'scheduled')->sortBy('effective_from')->first(),
            'vessels' => $live->filter(fn ($f) => $f->route_id === $r->id && $f->transport_unit_id)->values(),
        ]);

        $currentRates = $live->where('state', 'current')->map(fn ($f) => $f->basisRate($cargoType->measurement_unit))->filter(fn ($v) => $v > 0);
        $since = now()->subDays(30);
        $recent = CargoItem::where('cargo_type_id', $cargoType->id)->where('status', '!=', 'cancelled')->where('created_at', '>=', $since);

        $kpis = [
            'routes_priced' => $priceCards->filter(fn ($c) => $c['current'] || $c['vessels']->where('state', 'current')->count())->count(),
            'routes_total' => $routes->count(),
            'rate_min' => $currentRates->min(),
            'rate_max' => $currentRates->max(),
            'upcoming' => $live->where('state', 'scheduled')->count(),
            'lines_30d' => (clone $recent)->count(),
            'revenue_30d' => (float) (clone $recent)->sum('final_fare'),
        ];

        $bookings = CargoItem::with(['consignment:id,consignment_number,status,payment_status,route_id,transport_service_id', 'consignment.route:id,name,code',
                'consignment.transportService:id,departure_date,departure_time'])
            ->where('cargo_type_id', $cargoType->id)
            ->latest('id')->limit(50)->get();

        $changes = DualControlRequest::with(['maker:id,first_name,last_name,email', 'checker:id,first_name,last_name,email'])
            ->where(fn ($q) => $q->where('subject_key', "cargo_type:{$cargoType->id}")
                ->orWhere('subject_key', 'like', "cargo_fare:{$cargoType->id}:%"))
            ->latest('id')->limit(30)->get();

        return view('admin.cargo.types.show', [
            'type' => $cargoType,
            'priceCards' => $priceCards,
            'versions' => $versions,
            'usage' => $usage,
            'label' => $label,
            'kpis' => $kpis,
            'bookings' => $bookings,
            'changes' => $changes,
            'unitLabel' => ['kg' => 'kg', 'cbm' => 'cubic metre', 'unit' => 'piece', 'lane_meter' => 'vehicle'][$cargoType->measurement_unit] ?? $cargoType->measurement_unit,
        ]);
    }
}
