<?php

namespace App\Http\Controllers\Cargo\Concerns;

use App\Exceptions\CargoPaymentException;
use App\Exceptions\CargoPricingException;
use App\Models\CargoLock;
use App\Models\Consignment;
use App\Models\PaymentMethod;
use App\Models\TransportService;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoFareCalculator;
use App\Services\Cargo\CargoInventoryService;
use App\Services\Cargo\CargoPaymentService;
use App\Support\Msisdn;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The cargo booking flow shared by the admin booking terminal and the sales portal:
 * departures with free space, live quote, space hold, booking + first payment, customer autofill.
 * Prices always come from CargoFareCalculator; the browser never sends one.
 */
trait BuildsCargoCart
{
    /**
     * Bookable departures for one day (`date`) or a window (`from` + `days`, default the next 3 days),
     * optionally for one route, each with its free space.
     */
    protected function bookableDepartures(Request $request): Collection
    {
        $request->validate([
            'date' => 'nullable|date',
            'from' => 'nullable|date',
            'days' => 'nullable|integer|min:1|max:14',
            'route_id' => 'nullable|integer',
        ]);

        if ($request->filled('date')) {
            $from = $to = $request->date('date')->toDateString();
        } else {
            $start = $request->date('from');
            $from = ($start && $start->isAfter(today()) ? $start : today())->toDateString();
            $to = \Illuminate\Support\Carbon::parse($from)->addDays((int) $request->input('days', 3) - 1)->toDateString();
        }

        $inventorySvc = app(CargoInventoryService::class);
        $calculator = app(CargoFareCalculator::class);
        $priced = [];

        return TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'cargoInventory'])
            ->availableForBooking()
            ->whereDate('departure_date', '>=', $from)
            ->whereDate('departure_date', '<=', $to)
            ->when($request->filled('route_id'), fn ($q) => $q->where('route_id', $request->integer('route_id')))
            ->orderBy('departure_date')
            ->orderBy('departure_time')
            ->get()
            ->map(function (TransportService $s) use ($inventorySvc, $calculator, &$priced) {
                [$capacity, $space] = $inventorySvc->spaceSummary($s->cargoInventory);
                $s->setAttribute('capacity', $capacity);
                $s->setAttribute('space', $space);
                // Only cargo types with a fare in force can be booked on this departure
                $priced[$s->route_id . ':' . $s->transport_unit_id] ??= $calculator->pricedTypeIds($s->route_id, $s->transport_unit_id);
                $s->setAttribute('priced_type_ids', $priced[$s->route_id . ':' . $s->transport_unit_id]);

                return $s;
            });
    }

    /**
     * Live price for the cart (called as the clerk types).
     */
    protected function quoteCart(Request $request): array
    {
        $request->validate(['transport_service_id' => 'required|exists:transport_services,id'] + $this->cartRules());
        $discount = $this->authorisedDiscount($request);

        try {
            return app(CargoFareCalculator::class)->calculateForItems($request->items, (int) $request->transport_service_id, 'TZS', $discount);
        } catch (CargoPricingException $e) {
            throw $e->toValidationException();
        }
    }

    /**
     * Hold space for the cart. Passing replace_lock_id swaps the clerk's previous hold instead of adding one.
     */
    protected function holdCart(Request $request): CargoLock
    {
        $request->validate([
            'transport_service_id' => 'required|exists:transport_services,id',
            'replace_lock_id' => 'nullable|integer',
        ] + $this->cartRules());

        try {
            // Price first so an unpriceable cart never holds space
            app(CargoFareCalculator::class)->calculateForItems($request->items, (int) $request->transport_service_id);

            return app(CargoInventoryService::class)->acquireLock(
                (int) $request->transport_service_id,
                $request->items,
                $request->user()->id,
                $request->user()->agent_id ?? null,
                $request->replace_lock_id ? (int) $request->replace_lock_id : null,
            );
        } catch (CargoPricingException $e) {
            throw $e->toValidationException();
        } catch (\Exception $e) {
            abort(response()->json(['message' => $e->getMessage()], 422));
        }
    }

    /**
     * Only the clerk who took a hold can release it; an already-expired hold is a no-op.
     */
    protected function releaseHold(Request $request): void
    {
        $request->validate(['lock_id' => 'required|integer']);

        $lock = CargoLock::where('id', $request->lock_id)->where('user_id', $request->user()->id)->first();
        if ($lock) {
            app(CargoInventoryService::class)->releaseLock($lock);
        }
    }

    /**
     * Re-price the cart, book it against the clerk's own hold and start the first payment, all or nothing.
     *
     * @return array{0: Consignment, 1: \App\Models\CargoPayment}
     */
    protected function bookCart(Request $request): array
    {
        $validated = $request->validate([
            'lock_id' => 'required|integer',
            'transport_service_id' => 'nullable|exists:transport_services,id',
            'sender_name' => 'required|string|max:100',
            'sender_phone' => 'required|string|max:20',
            'sender_email' => 'nullable|email|max:150',
            'sender_id_type' => 'nullable|string|max:30',
            'sender_id_number' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:100',
            'receiver_phone' => 'required|string|max:20',
            'receiver_email' => 'nullable|email|max:150',
            'receiver_id_type' => 'nullable|string|max:30',
            'receiver_id_number' => 'nullable|string|max:50',
            'special_instructions' => 'nullable|string|max:1000',
            'payment_method' => ['required', Rule::exists('payment_methods', 'slug')->where('is_active', true)],
            'payer_msisdn' => 'nullable|string|max:20',
        ] + $this->cartRules());

        $phones = $this->normalisePhones($request, ['sender_phone', 'receiver_phone']);
        $discount = $this->authorisedDiscount($request);

        // Only the clerk's own hold, and (when the client says which) for the departure on screen
        $lock = CargoLock::where('id', $validated['lock_id'])->where('user_id', $request->user()->id)->first();
        $expected = $validated['transport_service_id'] ?? null;
        if (!$lock || ($expected && (int) $lock->transport_service_id !== (int) $expected)) {
            abort(response()->json(['message' => 'The space hold has expired. Go back to the cargo step to hold space again.'], 422));
        }

        // Reject an unusable channel / payer number before anything is booked
        $method = PaymentMethod::where('slug', $validated['payment_method'])->firstOrFail();
        $paymentSvc = app(CargoPaymentService::class);
        try {
            $paymentSvc->assertCanStart($method, $validated['payer_msisdn'] ?? null);
            // Priced at the booking moment: when the space was held, the price the clerk saw
            $calculation = app(CargoFareCalculator::class)->calculateForItems($validated['items'], $lock->transport_service_id, 'TZS', $discount, $lock->created_at);
        } catch (CargoPaymentException $e) {
            abort(response()->json(['message' => $e->getMessage()], 422));
        } catch (CargoPricingException $e) {
            throw $e->toValidationException();
        }

        $this->assertVehicleDetails($calculation['items'], $validated['items']);

        // Priced lines + what the clerk typed that pricing doesn't use
        $lines = collect($calculation['items'])->map(function (array $line, int $i) use ($validated) {
            $input = $validated['items'][$i];

            return array_merge($line, [
                'description' => $input['description'] ?? null,
                'vehicle_reg_number' => filled($input['vehicle_reg_number'] ?? null) ? strtoupper(trim($input['vehicle_reg_number'])) : null,
                'driver_name' => $input['driver_name'] ?? null,
                'driver_phone' => filled($input['driver_phone'] ?? null) ? $this->normalisePhone($input['driver_phone']) : null,
            ]);
        })->all();

        $service = TransportService::findOrFail($lock->transport_service_id);
        $data = array_merge($validated, $phones, [
            'route_id' => $service->route_id,
            'currency' => 'TZS',
            'net_amount' => $calculation['net_amount'],
            'total_discount' => $calculation['total_discount'],
            'discount_reason' => $request->discount_reason,
            'breakdown' => $calculation,
            'has_hazmat' => $calculation['has_hazmat'],
            'has_fragile' => $calculation['has_fragile'],
            'has_livestock' => $calculation['has_livestock'],
        ]);

        try {
            // Booking (space held as pending_payment) and its first payment attempt succeed or fail together.
            // Cash completes immediately; push / Lipa Namba complete from the payment panel.
            return DB::transaction(function () use ($lock, $data, $lines, $paymentSvc, $method, $request, $validated) {
                $consignment = app(CargoBookingService::class)->confirmBooking($lock, $data, $lines, $request->user()->id);
                $payment = $paymentSvc->start($consignment, $method, $validated['payer_msisdn'] ?? null, $request->user());

                return [$consignment, $payment];
            });
        } catch (\Exception $e) {
            abort(response()->json(['message' => $e->getMessage()], 422));
        }
    }

    /**
     * Previous senders/receivers with this phone number, newest first (autofill at the counter).
     * $bookedBy limits the search to one clerk's own bookings (agents never see other agencies' customers).
     */
    protected function customerSuggestions(Request $request, ?int $bookedBy = null): Collection
    {
        $request->validate(['phone' => 'required|string|max:20']);
        $digits = substr(preg_replace('/\D/', '', $request->phone), -9);
        if (strlen($digits) < 9) {
            return collect();
        }

        $people = collect();
        foreach (['sender', 'receiver'] as $role) {
            Consignment::whereRaw("regexp_replace(coalesce({$role}_phone, ''), '\\D', '', 'g') LIKE ?", ['%' . $digits])
                ->when($bookedBy, fn ($q) => $q->where('booked_by_user_id', $bookedBy))
                ->latest()
                ->limit(10)
                ->get(["{$role}_name as name", "{$role}_phone as phone", "{$role}_id_type as id_type", "{$role}_id_number as id_number", 'created_at'])
                ->each(fn ($c) => $people->push([
                    'role' => $role,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'id_type' => $c->id_type,
                    'id_number' => $c->id_number,
                    'last_used' => $c->created_at?->toDateString(),
                ]));
        }

        // One entry per distinct name, most recent first
        return $people->sortByDesc('last_used')->unique(fn ($p) => mb_strtolower(trim($p['name'])))->take(5)->values();
    }

    /**
     * Validation rules for a cart of cargo lines (shared by quote, hold and confirm).
     */
    protected function cartRules(): array
    {
        return [
            'items' => 'required|array|min:1|max:50',
            'items.*.cargo_type_id' => 'required|integer|exists:cargo_types,id',
            'items.*.quantity' => 'nullable|integer|min:1|max:10000',
            'items.*.weight_kg' => 'nullable|numeric|min:0|max:100000',
            'items.*.length_m' => 'nullable|numeric|min:0|max:100',
            'items.*.width_m' => 'nullable|numeric|min:0|max:100',
            'items.*.height_m' => 'nullable|numeric|min:0|max:100',
            'items.*.volume_cbm' => 'nullable|numeric|min:0|max:10000',
            'items.*.is_fragile' => 'nullable|boolean',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.declared_value' => 'nullable|numeric|min:0|max:9999999999',
            'items.*.vehicle_reg_number' => 'nullable|string|max:30',
            'items.*.driver_name' => 'nullable|string|max:100',
            'items.*.driver_phone' => 'nullable|string|max:30',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_reason' => 'nullable|string|max:255',
        ];
    }

    /**
     * A fare override needs the cargo-override-fare permission and a reason.
     */
    protected function authorisedDiscount(Request $request): float
    {
        $discount = (float) $request->input('discount_amount', 0);
        if ($discount <= 0) {
            return 0;
        }

        abort_unless($request->user()->hasPermission('cargo-override-fare'), 403, 'You do not have permission to override fares.');
        if (mb_strlen(trim((string) $request->discount_reason)) < 5) {
            throw ValidationException::withMessages(['discount_reason' => 'Give a reason for the fare override (at least 5 characters).']);
        }

        return $discount;
    }

    /**
     * Tanzanian mobiles are stored as 0XXXXXXXXX; foreign numbers as +<digits>.
     */
    protected function normalisePhones(Request $request, array $fields): array
    {
        $out = [];
        $errors = [];
        foreach ($fields as $field) {
            $phone = $this->normalisePhone($request->input($field));
            if ($phone === null) {
                $errors[$field] = 'Enter a valid mobile number, e.g. 0754 123 456 or +254 712 345 678.';
            }
            $out[$field] = $phone;
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return $out;
    }

    protected function normalisePhone(?string $phone): ?string
    {
        if ($tz = Msisdn::normalize($phone)) {
            return '0' . substr($tz, 3);
        }
        $digits = preg_replace('/\D/', '', (string) $phone);

        return str_starts_with(trim((string) $phone), '+') && strlen($digits) >= 8 && strlen($digits) <= 15 ? '+' . $digits : null;
    }

    /**
     * Each vehicle travels as its own line with its registration number.
     */
    protected function assertVehicleDetails(array $lines, array $input): void
    {
        $errors = [];
        foreach ($lines as $i => $line) {
            if ($line['measurement_unit'] !== 'lane_meter') {
                continue;
            }
            if ($line['quantity'] > 1) {
                $errors["items.{$i}.quantity"] = 'Add each vehicle as its own line.';
            }
            if (blank($input[$i]['vehicle_reg_number'] ?? null)) {
                $errors["items.{$i}.vehicle_reg_number"] = 'Enter the vehicle registration number.';
            }
            if (filled($input[$i]['driver_phone'] ?? null) && $this->normalisePhone($input[$i]['driver_phone']) === null) {
                $errors["items.{$i}.driver_phone"] = 'Enter a valid phone number for the driver.';
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }
    }
}
