# Implementation Plan — Parcel Service and Contract Cargo

| | |
|---|---|
| **Document** | 03 of 3 |
| **Version** | 1.3, approved on 15 November 2026 (changes in section 12) |
| **Date** | 15 November 2026 |
| **Prepared for** | The project owner and IT; management for the phase order and effort |
| **Companion documents** | [01 Current-State Review](01_CURRENT_STATE_REVIEW.md) · [02 System Requirements Document](02_SYSTEM_REQUIREMENTS_DOCUMENT.md) |
| **Status** | Approved. Each phase is delivered in its own session, tracked in [04_TASK_TRACKER.md](04_TASK_TRACKER.md). |

---

## 1. Summary

The work is split into eight phases. Each phase is a separate task under the working agreement: it is implemented only after this plan is approved, each changed file is shown as it is edited, it ends with a walkthrough, and it stays uncommitted until you accept or reject it. Each phase leaves the system working and can be switched off by a setting.

| Phase | Outcome | Depends on | Indicative effort |
|---|---|---|---|
| **0 Foundations** | Safe automated tests on their own database; duty station per user; shared building blocks | — | 4–5 days |
| **1 Tariff** | Cargo types charged per CBM, per kg and on declared value in any combination, all added and nothing else; a fare holds only its type's amounts; parcels sold in TZS or USD | 0 | 8–10 days |
| **2 Parcel flow** | Receive → assign to a tray on a sailing (space held) → board → in transit → arrive → collect, with pieces, trays, big parcels assigned directly, a capacity ledger and the late-collection charge | 0, 1 | 22–28 days |
| **3 Customers, quotations, contracts** | Customer accounts, rate cards, quotations with revisions and approval, contracts, vessel calendar | 0 | 16–20 days |
| **4 Invoicing and receivables** | Invoices, credit notes, receipts, statements, ageing, tax codes, both currencies | 3 | 12–16 days |
| **5 Voyages, cargo operations, laytime** | Ad-hoc voyages, exclusive and shared capacity, check-in and tally, NOR, statement of facts, laytime, demurrage and despatch, safety gates | 2, 3, 4 | 20–26 days |
| **6 Time charter and project contracts** | Hire periods, off-hire, automatic hire invoices, contract rates, call-offs, commitments | 4, 5 | 10–14 days |
| **7 Insights and options** | Dashboard, voyage estimate, choosing the sailing at the counter, certificates, incidents, tide windows, fiscal connection, customer portal | as chosen | per item |

Phases 0 to 2 put the parcel service live (about 7 to 9 working weeks). Phases 3 and 4 let the commercial team quote, contract and invoice. Phase 5 completes contract cargo operations. Phases 3 and 4 do not depend on Phase 2, so they can start earlier if contract cargo is the priority (decision D-24 in document 02).

Effort is implementation and automated tests. It does not include your review and testing time between phases.

---

## 2. Findings this plan responds to

The findings are in [document 01](01_CURRENT_STATE_REVIEW.md). This table shows where each is resolved.

| Finding | Resolved in |
|---|---|
| F-01 parcel tied to a sailing at booking; F-04 no received stage; F-05 cannot move to another sailing | Phase 2 |
| F-02 no tray; F-03 no piece tracking; F-06 arrival without unpacking; F-07 scanner limits | Phase 2 |
| F-08 status names | Phase 2 (rename and conversion) |
| F-09 one charge basis; F-10 value charge per fare; F-13 weight bands as types | Phase 1 |
| F-12 TZS only | Phase 1 (parcels), Phase 4 (documents) |
| F-14 surcharges; storage | Phase 1 removes the surcharges from parcel pricing; Phase 2 adds the late-collection charge |
| F-15 placeholder fares | Tariff is re-keyed by the business after Phase 1 |
| F-16 no customers; F-17 no quotation, contract, invoice | Phases 3 and 4 |
| F-18 no charter; F-20 vessel record | Phases 3 and 5 |
| F-19 no port time | Phase 5 |
| F-21 agency float | Not reused |
| F-22 tests on the development database | Phase 0 |
| F-23 capacity counters | Phase 2 (ledger), Phase 5 (exclusive and shared) |
| F-24 scattered statuses | Phase 0 (definition), Phase 2 (use) |
| F-25 leftovers | Phase 0 removes only the failing nightly command |
| F-26 duplicate endpoints | New endpoints are added in one place per portal |
| F-27 money in two styles | Phases 1 and 4 |

---

## 3. Approach

| # | Decision | Reason |
|---|---|---|
| AD-01 | **One shipment core.** `consignments` gains a `service_type` (parcel or cargo). Contract cargo is carried as consignments linked to a cargo booking. | The manifest, tracking, labels, hand-over and capacity work for both lines without a second copy. |
| AD-02 | **Pieces are what staff handle.** A new `cargo_pieces` table holds one row per labelled package or lot. The consignment's status is a roll-up of its pieces. | Trays, split sailings and discrepancies need it. |
| AD-03 | **Statuses are defined once**, as PHP enums with a state machine that lists allowed moves per service type. Blade, Vue, reports, SMS and tracking read labels from it. | Removes the scattered text values (F-24). |
| AD-04 | **Capacity ledger.** A new `capacity_allocations` table is the record of everything that takes space on a voyage: assigned and boarded trays, parcels and big parcels from Phase 2, cargo bookings and holds from Phase 5. The totals on `cargo_inventories` remain as a fast cache, updated in the same transaction under the existing row lock, and rebuilt from the ledger by `cargo:recount-inventory`. | Several sources now take space from one vessel (F-23). |
| AD-05 | **Charge bases on the cargo type.** `cargo_types.charge_bases`, a list from CBM, weight, declared value and per piece. Every basis in the list is added. The fare keeps its existing rate columns, and its form and validation use only the type's bases. `measurement_unit` is kept for display and derived from the bases. Nothing else is added to a parcel's price: the surcharges stop applying, and the fare's minimum charge becomes an optional flat price, the least a line pays. | The fare table already has every rate (F-11); the change is small and reversible. Your rule: the price depends on the cargo type's configuration. |
| AD-06 | **Money as amount, currency, rate, TZS equivalent** on every new table, with one `Money` helper for rounding and formatting. | Consistent dual currency (F-27). |
| AD-07 | **Documents are versioned and frozen when issued.** PDFs come from the existing dompdf package. Numbers come from a `document_sequences` row locked while a number is taken. | Auditability; continuous numbering. |
| AD-08 | **Approvals use the existing dual-control registry.** New operations and handlers are added to `config/dual_control.php`; no second approval engine. | Already built, understood and audited. |
| AD-09 | **The laytime calculator is a pure function**: terms and events in, statement out. It reads no database and has fixed test cases. | Disputes are settled by reproducing the calculation. |
| AD-10 | **Screens by task.** Back-office work (commercial, finance, the dispatch board, set-up) goes in the admin portal. Counter, quay and vessel tasks go in the sales portal, which already works on phones. Shared rules stay in traits and services, as `BuildsCargoCart` does today. | Matches how each group works. |
| AD-11 | **Each service line has a switch** in settings (`parcel_receive_flow_enabled`, `contract_cargo_enabled`). | A phase can be accepted, rejected or paused cleanly. |
| AD-12 | **New code lives beside the current cargo code**, in `App\Services\Cargo\{Parcel, Commercial, Finance, Voyage, Laytime}`. Passenger-era classes are not extended or cleaned up, apart from the nightly command that fails. | Keeps the change focused. |
| AD-13 | **Space is held at assignment; boarding only changes the status.** The assigner opens a tray for a sailing; each parcel scanned in holds its weight and volume on that sailing under the sailing's row lock, and is refused if the tray or the sailing is full. Big parcels and vehicles are assigned to a sailing directly with the same check. Boarding refuses anything not assigned to the sailing being loaded. Nothing is held at the counter: the `cargo_locks` table, its model, the hold settings and `cargo:clear-locks` are removed. A parcel is priced by its route, at the moment its quote was shown. | Your second comments: the assigner assigns parcels to the tray and the tray to the ferry, and boarding only changes the status, so a vessel is never overboarded. |
| AD-14 | **Demurrage and despatch are data on the booking, not rules in code**: the rate, its unit (day or hour), how a part unit is treated, and the despatch as a percentage of demurrage, its own rate, or none. | Your comment 5: the contract decides. |
| AD-15 | **The late-collection charge is a second payment on the consignment**, using the existing payment channels, with its own receipt and ledger category. A percentage setting (0 = no charge) and a grace period control it, and the values in force when a parcel is received apply to it. | Your note and second comments: a percentage in system settings, 0 for none, with a grace period. |

---

## 4. Database changes

All changes are migrations with a working `down()`. A `pg_dump` is taken before each phase's migrations.

### 4.1 Existing tables that change

| Table | Change | Phase |
|---|---|---|
| `users` | `+ station_id` (duty station, optional) | 0 |
| `stations` | `+ has_parcel_office`; `station_type` gains the value `landing_site` | 0, 5 |
| `consignments` | `+ service_type` (default `parcel`) | 0 |
| `cargo_types` | `+ charge_bases` (a list from `volume`, `weight`, `value`, `unit`; default `["unit"]`) | 1 |
| `cargo_items` | `+ unit_charge`, `+ weight_charge`, `+ volume_charge` | 1 |
| `consignments` | `exchange_rate` is now filled at booking | 1 |
| `cargo_types` | `+ tray_allowed` (false for vehicles), `+ tracking_mode` (`piece` or `lot`) | 2 |
| `consignments` | `transport_service_id` becomes optional; `+ origin_station_id`, `+ destination_station_id`, `+ received_at`, `+ received_by`, `+ received_station_id`, `+ not_before_date`, `+ expected_service_id`, `+ expected_departure_at`, `+ hold_reason`, `+ held_at`, `+ held_by`, `+ pieces_total`, `+ pieces_assigned`, `+ pieces_boarded`, `+ pieces_arrived`, `+ pieces_collected`, `+ ready_at`, `+ late_collection_terms` (the percentage, grace period and rule in force at receipt), `+ late_collection_amount`, `+ late_collection_paid_at`, `+ late_collection_waived_by`, `+ late_collection_waive_reason`; status values converted | 2 |
| `cargo_items` | status values converted | 2 |
| `tracking_events` | `+ cargo_piece_id`, `+ tray_load_id`, `+ station_id` | 2 |
| `cargo_payments` | `+ purpose` (`booking` or `late_collection`) | 2 |
| `cargo_locks` | Dropped: nothing is held at the counter any more | 2 |
| `cargo_capacities` | `+ tray_slots` | 2 |
| `cargo_inventories` | `+ total_tray_slots`, `+ used_tray_slots`, `+ held_tray_slots` | 2 |
| `transport_services` | `+ parcel_cutoff_minutes`, `+ eta_at` | 2 |
| `transport_units` | `+ vessel_class`, `+ is_charterable`, `+ max_passengers` | 3 |
| `cargo_capacities` | `+ deadweight_cargo_t`, `+ deck_area_m2`, `+ deck_strength_t_m2`, `+ ramp_width_m`, `+ ramp_swl_t`, `+ max_cargo_height_m`, `+ unit_slots` | 3 |
| `consignments` | `+ customer_id`, `+ payment_terms` (`prepaid`, `account`, `collect`) | 3, 4 |
| `transactions` | `+ customer_id`, `+ invoice_id`, `+ customer_receipt_id` | 4 |
| `transport_services` | `schedule_id` becomes optional; `+ service_kind` (`scheduled`, `charter`, `adhoc`), `+ voyage_number`, `+ is_exclusive`, `+ cargo_booking_id`, `+ delay_reason`, `+ checklist_signed_at`, `+ checklist_signed_by` | 5 |
| `cargo_inventories` | `+ total_deck_area_m2`, `+ used_deck_area_m2`, `+ held_deck_area_m2`, `+ total_units`, `+ used_units`, `+ held_units` | 5 |
| `consignments` | `+ cargo_booking_id` | 5 |
| `cargo_items` | `+ unit_ref`, `+ verified_weight_kg`, `+ verified_at`, `+ verified_by`, `+ checked_in_at`, `+ dg_packing_group`, `+ dg_emergency_contact` | 5 |

### 4.2 New tables

**Phase 0**

```text
document_sequences   id, series, year, prefix, next_number, padding            unique(series, year)
attachments          id, attachable_type, attachable_id, kind, file_path, original_name,
                     mime, size, uploaded_by, created_at
```

**Phase 1**

```text
cargo_fare_tiers     id, cargo_fare_id, basis, from_qty, to_qty, rate_tzs, rate_usd, is_flat
```

**Phase 2**

```text
cargo_pieces         id, consignment_id, cargo_item_id, piece_no, quantity (packages in a lot, default 1),
                     label_code (unique), weight_kg, volume_cbm, status, station_id,
                     tray_load_id, transport_service_id (set at assignment; follows its tray),
                     storage_location, condition, received_at, assigned_at, assigned_by, boarded_at,
                     arrived_at, ready_at, collected_at, is_loose, missed_sailings, timestamps
trays                id, code (unique), tray_type, tare_weight_kg, max_weight_kg, max_volume_cbm,
                     home_station_id, current_station_id, current_load_id, status, notes,
                     timestamps, deleted_at
tray_loads           id, load_number (unique), tray_id, origin_station_id, destination_station_id,
                     transport_service_id (the sailing it is opened for), status, seal_number,
                     pieces_count, gross_weight_kg, volume_cbm, has_hazmat, has_fragile, opened_by,
                     opened_at, closed_by, closed_at, boarded_by, boarded_at, arrived_at, unpacked_by,
                     unpacked_at, missed_sailings, discrepancy (json), timestamps
capacity_allocations id, transport_service_id, source_type (tray_load, cargo_piece, cargo_booking, hold),
                     source_id, state (held = assigned, confirmed = boarded or firm, released),
                     weight_kg, volume_cbm, lane_meters, deck_area_m2, tray_slots, units, is_exclusive,
                     expires_at, created_by, released_at, release_reason, timestamps
                     index(transport_service_id, state); unique(source_type, source_id) while not released
cargo_exceptions     id, consignment_id, cargo_piece_id, tray_load_id, transport_service_id, kind
                     (damage, shortage, surplus, hold, other), description, status, outcome,
                     reported_by, closed_by, closed_at, timestamps
```

**Phase 3**

```text
customers            id, uuid, customer_no (unique), type, legal_name, trading_name, tin, vrn,
                     vat_exempt, exemption_ref, exemption_expires, withholding_applies, address,
                     city, country_id, default_currency, payment_terms_days, credit_limit,
                     credit_currency, credit_status, status, account_manager_id, notes,
                     created_by, timestamps, deleted_at
customer_contacts    id, customer_id, name, title, phone, email, is_commercial, is_operations,
                     is_accounts, is_signatory, is_primary, status, timestamps
rate_cards           id, rate_type (daily_hire, monthly_hire, voyage_lump_sum, per_tonne, per_cbm,
                     per_lane_metre, per_unit, mobilisation, demurrage_daily), transport_unit_id,
                     vessel_class, route_id, cargo_type_id, unit_label, currency, amount,
                     floor_amount, effective_from, effective_until, version, previous_id, status,
                     change_reason, created_by, ended_by, ended_at, dual_control_request_id, timestamps
terms_templates      id, code, title, contract_type, body, is_default, status, version, timestamps
quotations           id, uuid, quote_no, revision, root_id, customer_id, contact_id, contract_type,
                     vessel_use, transport_unit_id, vessel_class, load_station_id,
                     discharge_station_id, route_id, cargo_summary (json), laycan_from, laycan_to,
                     period_from, period_to, currency, fx_rate_to_tzs, subtotal, tax_total, total,
                     valid_until, terms (json), special_conditions, status, owner_id, approved_by,
                     approved_at, sent_at, sent_to, accepted_at, acceptance_evidence (json),
                     decline_reason, hold_until, dual_control_request_id, timestamps
quotation_lines      id, quotation_id, line_no, charge_code, description, basis, unit_label,
                     quantity, unit_rate, amount, tax_code_id, tax_amount, rate_source,
                     rate_card_amount, below_floor, notes
cargo_contracts      id, uuid, contract_no, customer_id, quotation_id, contract_type, vessel_use,
                     transport_unit_id, currency, value_total, starts_at, ends_at, terms (json, frozen),
                     commitment (json), status, signed_at, signed_by_name, account_manager_id,
                     timestamps
contract_amendments  id, cargo_contract_id, amendment_no, changes (json), reason, status,
                     approved_by, effective_at, dual_control_request_id, timestamps
cargo_bookings       id, uuid, booking_no, cargo_contract_id, customer_id, booking_kind (voyage,
                     shipment, hire_period, call_off), vessel_use, transport_unit_id,
                     transport_service_id, load_station_id, discharge_station_id, laycan_from,
                     laycan_to, period_from, period_to, reserved_weight_t, reserved_volume_cbm,
                     reserved_deck_area_m2, reserved_lane_meters, reserved_units, cargo_description,
                     consignee_name, consignee_phone, currency, freight_amount, status, timestamps
vessel_calendar_entries  id, transport_unit_id, kind (time_charter, charter_voyage, hold,
                     maintenance, out_of_service), starts_at, ends_at, cargo_booking_id,
                     quotation_id, state (tentative, firm, released), notes, created_by, timestamps
                     exclusion constraint: no two firm entries for one vessel overlap
```

**Phase 4**

```text
tax_codes            id, code, name, rate_pct, is_default, status, timestamps
invoices             id, uuid, document_type (invoice, proforma, credit_note), invoice_no,
                     invoice_kind (deposit, freight, hire, demurrage, storage, parcel_account, other),
                     customer_id, cargo_contract_id, cargo_booking_id, related_invoice_id, currency,
                     fx_rate_to_tzs, issue_date, due_date, subtotal, tax_total, total, total_tzs,
                     amount_paid, balance, status, notes, fiscal_status, fiscal_ref, created_by,
                     approved_by, issued_at, voided_at, voided_by, void_reason,
                     dual_control_request_id, timestamps
invoice_lines        id, invoice_id, line_no, description, quantity, unit_label, unit_rate, amount,
                     tax_code_id, tax_rate_pct, tax_amount, source_type, source_id
customer_receipts    id, uuid, receipt_no, customer_id, received_at, method, payment_method_id,
                     reference, currency, amount, fx_rate_to_tzs, amount_tzs, withholding_amount,
                     withholding_cert_no, unallocated_amount, status, recorded_by, confirmed_by,
                     notes, timestamps
receipt_allocations  id, customer_receipt_id, invoice_id, amount_invoice_currency,
                     amount_receipt_currency, fx_rate_used, fx_difference_tzs, allocated_by,
                     allocated_at
```

**Phase 5**

```text
cargo_bookings       + laytime_load_minutes, laytime_discharge_minutes, laytime_reversible,
                     laytime_basis (shinc, shex_unless_used, shex_even_if_used), working_hours (json),
                     turn_time_minutes, commence_on_start, exceptions (json), once_on_demurrage,
                     demurrage_rate, demurrage_currency, demurrage_unit (day, hour),
                     demurrage_part_unit (pro_rata, in_full),
                     despatch_mode (none, percent_of_demurrage, own_rate), despatch_pct, despatch_rate,
                     despatch_unit (day, hour), despatch_part_unit (pro_rata, in_full),
                     despatch_basis (laytime_saved, all_time_saved),
                     detention_after_days, detention_rate
port_calls           id, transport_service_id, station_id, purpose (load, discharge), arrived_at,
                     nor_tendered_at, nor_tendered_by, nor_method, nor_accepted_at,
                     nor_accepted_by_name, berthed_at, ops_commenced_at, ops_completed_at,
                     documents_at, departed_at, remarks, timestamps
port_events          id, port_call_id, cargo_booking_id, event_code, started_at, ended_at,
                     counts_override, override_reason, remarks, recorded_by, timestamps, deleted_at
laytime_statements   id, cargo_booking_id, version, status, terms_snapshot (json), allowed_minutes,
                     used_minutes, demurrage_minutes, saved_minutes, result, rate, currency, amount,
                     breakdown (json), computed_at, computed_by, approved_by, approved_at,
                     customer_response, invoice_id, dual_control_request_id, timestamps
voyage_checklists    id, transport_service_id, template_code, answers (json), signed_by, signed_at,
                     timestamps
public_holidays      id, holiday_date, name
```

**Phase 6**

```text
hire_periods         id, cargo_booking_id, delivered_at, delivery_station_id, redelivery_due_at,
                     redelivered_at, redelivery_station_id, rate, rate_unit (day, month), currency,
                     invoice_interval_days, fuel_on_delivery (json), fuel_on_redelivery (json),
                     status, timestamps
off_hire_events      id, hire_period_id, started_at, ended_at, reason_code, remarks, credit_amount,
                     approved_by, invoice_id, timestamps
contract_rates       id, cargo_contract_id, route_id, cargo_type_id, basis, unit_label, currency,
                     rate, min_charge, effective_from, effective_until, timestamps
```

---

## 5. Phases

Paths are relative to `cargo/`. "New" files are created; "changed" files exist today.

### Phase 0 — Foundations

**Goal.** Make it safe to change the system, and lay the pieces every later phase uses.

**What you will see.** A duty station on each user. No change to booking.

**Work**

1. A separate test database, `uclp_cargo_test`, built by a script that copies the structure and reference tables of the development database. Tests refuse to run against any database whose name does not end in `_test`. The password leaves `phpunit.xml`.
2. Rollback added to the four cargo test files that lack it, and a `cargo` test suite that lists the files safe to run.
3. Status enums and a state machine (not yet used by the flows).
4. `Money`, document numbering and attachments.
5. Duty station on users.
6. The nightly `voyage:generate` entry is removed from the scheduler.
7. The two service-line switches, both off.

| | Files |
|---|---|
| **New** | `database/migrations/2026_11_xx_100000_cargo_foundations.php` · `app/Enums/ServiceType.php` · `app/Enums/ConsignmentStatus.php` · `app/Enums/ChargeBasis.php` · `app/Services/Cargo/ConsignmentStateMachine.php` · `app/Support/Money.php` · `app/Models/DocumentSequence.php` · `app/Models/Attachment.php` · `app/Services/DocumentNumberService.php` · `app/Services/AttachmentService.php` · `scripts/make-test-db.sh` · `.env.testing.example` · `tests/Unit/MoneyTest.php` · `tests/Unit/ConsignmentStateMachineTest.php` · `tests/Feature/DocumentNumberingTest.php` |
| **Changed** | `phpunit.xml` · `tests/TestCase.php` · `tests/Feature/CargoAdminCrudTest.php` · `tests/Feature/CargoBookingFlowTest.php` · `tests/Feature/CargoPermissionsTest.php` · `tests/Feature/CargoPortalAuthTest.php` · `app/Models/User.php` · `app/Models/Station.php` · `app/Models/Consignment.php` · `app/Http/Controllers/Admin/UserController.php` · `resources/views/admin/users/create.blade.php` · `resources/views/admin/users/show.blade.php` · `resources/views/admin/users/index.blade.php` · `routes/console.php` |

**Exit criteria.** The cargo suite passes on the test database and leaves the development database untouched (row counts compared before and after). Ten document numbers taken in parallel are consecutive.

---

### Phase 1 — Tariff

**Goal.** SRS 4.1 and the parcel part of 4.16: charge bases per cargo type, all used together; TZS or USD at the counter.

**What you will see.** The cargo-type form has tick boxes for per CBM, per kg and percentage of declared value, plus per piece as today. Any combination can be ticked, and everything ticked is added. The fare editor shows an amount field for each ticked basis and nothing else: a type charged on declared value alone shows only the percentage; a type "Fragile" ticked for CBM, weight and value requires all three. An optional flat price, offered for every type, is the least a parcel pays. The booking screen asks only for what the type needs and shows each part of the price. No surcharge is added. A currency switch appears for users with the USD permission.

**Surcharges and flat price.** A parcel's price is only the sum of its cargo type's charges. The fragile, livestock and dangerous-goods surcharges are removed from the calculator, and their settings (`cargo_fragile_surcharge_pct`, `cargo_hazmat_flat_fee_tzs`, `cargo_hazmat_flat_fee_usd`) are retired; the fragile and dangerous-goods marks stay for labels, tray rules and the manifest. The fare's minimum charge becomes the flat price: optional, offered for every cargo type, the least a line pays. It reuses the `min_charge_tzs` and `min_charge_usd` columns, and each line's `min_charge` keeps the amount it was priced with. Rate bands apply each band's rate only to the quantity within it. Removing the surcharges changes prices for the existing fragile and hazardous types, which hold only test data.

**Database.** `cargo_types.charge_bases`; `cargo_items.unit_charge`, `weight_charge`, `volume_charge`; `cargo_fare_tiers`; settings `cargo_usd_selling_enabled`, `cargo_usd_fallback_convert`; permission `cargo-sell-usd`; the surcharge settings removed.

**Conversion of existing types.** `kg` becomes weight, `cbm` becomes volume, `unit` and `lane_meter` become per piece. The value basis is added to a type when its live fares carry a value percentage. The migration checks one rule before and after: **the set of type-and-route pairs that can be booked must be identical.** If adding the value basis would hide a pair, it is left off for that type and reported. In the development data this affects three types (TV/Screen, General Freight, SUV).

| | Files |
|---|---|
| **New** | `database/migrations/…_add_charge_bases_to_cargo_types.php` · `…_add_component_charges_to_cargo_items.php` · `…_create_cargo_fare_tiers_table.php` · `…_cargo_usd_selling_setup.php` · `…_retire_parcel_surcharges.php` · `app/Models/CargoFareTier.php` · `tests/Feature/CargoChargeBasesTest.php` · `tests/Feature/CargoUsdSellingTest.php` |
| **Changed — pricing** | `app/Models/CargoType.php` · `app/Models/CargoFare.php` (`basisRate`, `basisField` replaced by a check of every declared basis) · `app/Models/CargoItem.php` · `app/Services/Cargo/CargoFareCalculator.php` · `app/Services/Cargo/CargoFareService.php` · `app/Services/DualControl/Handlers/CargoTypeHandler.php` · `app/Services/DualControl/Handlers/CargoFareHandler.php` · `app/Services/DualControl/DualControlPresenter.php` |
| **Changed — booking and money** | `app/Http/Controllers/Cargo/Concerns/BuildsCargoCart.php` · `app/Services/Cargo/CargoBookingService.php` · `app/Services/Cargo/CargoReceiptService.php` · `app/Http/Controllers/Cargo/Admin/AdminCargoController.php` · `app/Http/Controllers/Cargo/Admin/CargoFareController.php` · `app/Http/Controllers/Cargo/Admin/CargoTypeProfileController.php` · `app/Http/Controllers/Cargo/Portal/PortalCargoController.php` |
| **Changed — totals by currency** | `app/Services/Cargo/CargoReportService.php` · `app/Services/Cargo/CargoDashboardService.php` · `app/Services/Cargo/CloseOfBusinessService.php` · `app/Services/ShiftService.php` |
| **Changed — admin screens** | `resources/views/admin/cargo/partials/type-form.blade.php` · `types.blade.php` · `types/show.blade.php` · `partials/fare-editor.blade.php` · `partials/route-rate-card.blade.php` · `fares.blade.php` · `booking.blade.php` · `bookings/show.blade.php` · `consignments.blade.php` · `dashboard.blade.php` · `resources/views/admin/dual_control/partials/cargo-pricing.blade.php` · `resources/views/admin/shifts/show.blade.php` · `resources/views/cargo/receipt.blade.php` |
| **Changed — sales portal** | `resources/js/portal/stores/cargo.js` · `components/booking/CargoLines.vue` · `components/booking/OrderSummary.vue` · `components/booking/ReviewPay.vue` · `components/shift/ShiftSummary.vue` · `views/cargo/CargoBookingDetailView.vue` · `views/DashboardView.vue` · `utils/cargoUi.js` |
| **Changed — set-up and tests** | `config/dual_control.php` (wording) · `database/seeders/CargoTypeSeeder.php` · `database/seeders/CargoSystemConfigSeeder.php` · `tests/Feature/CargoPricingTest.php` · `CargoFareVersioningTest.php` · `CargoPortalBookingTest.php` · `CargoAdminScreensTest.php` |

**Exit criteria.** Examples A to E of the SRS produce the stated amounts in both portals, including all seven combinations of example D with only that combination's fields on the fare form. A fare that lacks a declared rate cannot be saved. No surcharge is added to any line, and no line costs less than its fare's flat price. The bookable set is unchanged by the migration. Receipts and the booking page show the breakdown. Cash-up shows TZS and USD separately. `npm run build` completes.

---

### Phase 2 — Parcel flow

**Goal.** SRS 4.2 to 4.6, VSL-04, TAR-13 (late-collection charge), TAR-17 (priced by route), and the parcel parts of notifications and reports.

**What you will see.** "Receive parcel" replaces "New cargo booking" for every cargo type, with no departure step, no space hold and an expected-sailing chip. An **Assign** screen, on a phone or at a desk: the assigner picks a coming sailing and sees its space left; opens a tray for it and scans parcels in, each one holding its space and refused if the tray or the sailing is full; assigns big parcels and vehicles to the sailing directly; seals trays; moves a tray, a big parcel or a whole sailing's load to another sailing. A **Boarding** screen where the port officer scans trays and big parcels at the vessel to mark them boarded; anything not assigned to that sailing is refused. A dispatch board showing what is waiting, what is assigned to each sailing, what missed a sailing, and the space left. An unpack screen at the destination. The manifest lists trays and big parcels, assigned and boarded. Statuses read Received, Assigned to tray (or to sailing), Boarded, In transit, Arrived, Collected. At hand-over, a late-collection charge when it applies, with its settings under Settings › Cargo.

**How space is held.** Opening a tray for a sailing writes a ledger entry for the tray's empty weight and position. Each parcel scanned in is checked (paid, not on hold, right destination, at this station, not already assigned, allowed in a tray, not before its requested date), then, under the sailing's row lock, against the tray's limits and the sailing's space left; if it fits, a ledger entry holds its weight and volume. A big parcel or vehicle is checked and held the same way, without a tray. Moving an item to another sailing releases its entries and writes new ones in one transaction. Boarding moves the entries from held to confirmed without changing the amounts. When a sailing departs, entries still held are released and their items listed as having missed it; cancelling a sailing releases everything on it. Today a cancelled journey keeps its bookings and refuses loading, so this release is new.

**Price without a sailing.** The calculator takes the route in place of a departure, and the vessel selector leaves the fare editor. The price moment is when the quote on screen was produced, honoured for 15 minutes (`cargo_quote_valid_minutes`); it used to be when the space was held.

**Late-collection charge.** Settings `cargo_late_collection_pct` (0 = no charge, the default), `cargo_late_collection_grace_days` (3) and `cargo_late_collection_repeat` (`once` or `daily`), in the Cargo group of the existing Settings page and changed through the existing settings approval. The values in force when a parcel is received are copied onto it (`consignments.late_collection_terms`). The rules are in SRS 5.9. The charge is a second payment on the consignment (`cargo_payments.purpose = late_collection`), posts to the existing "Storage Fees" ledger category, has its own receipt, and can be waived with the new permission `cargo-waive-late-charge`.

**Database.** The Phase 2 rows of section 4, plus a set-up migration for permissions, role grants, menus, SMS templates and settings.

**New services** (`app/Services/Cargo/Parcel/` unless stated)

| Service | Responsibility |
|---|---|
| `ParcelReceivingService` | Create the consignment, lines and pieces without a sailing; re-weigh, adjusting any held space |
| `ExpectedSailingEstimator` | The estimate in SRS 5.2 |
| `PieceLabelService` | Label and tray-tag PDFs |
| `TrayService` | Tray register; open a tray for a sailing, seal, reopen; tray location |
| `AssignmentService` | Assign parcels to trays and big parcels to sailings; remove; move a tray, a big parcel or a whole sailing's load to another sailing; release on a missed or cancelled sailing and propose the next one with room |
| `DispatchBoardService` | The board's figures: waiting items and their age, trays and their sailing, missed sailings, space left; the forecast for unassigned items |
| `BoardingService` | Board and un-board: a status change after checking the item is assigned to the sailing being loaded, sealed if a tray, and not on hold |
| `LateCollectionCharge` | Whether the charge applies, its amount, its payment and its waiver |
| `ArrivalService` | Arrive, unpack, discrepancies, ready for collection |
| `ParcelExceptionService` | Hold and release, damage, shortage, return |
| `PieceRollup` | Recompute a consignment's status and counters from its pieces |
| `app/Services/Cargo/CapacityLedger.php` | Hold, confirm, move and release space under the sailing's row lock |

| | Files |
|---|---|
| **New — database** | `…_parcel_flow_core.php` · `…_create_cargo_pieces_table.php` · `…_create_trays_and_tray_loads_tables.php` · `…_create_capacity_allocations_table.php` · `…_create_cargo_exceptions_table.php` · `…_tracking_events_piece_and_tray_refs.php` · `…_retire_cargo_locks.php` · `…_late_collection_charge.php` · `…_parcel_flow_setup.php` |
| **New — models and enums** | `app/Models/CargoPiece.php` · `Tray.php` · `TrayLoad.php` · `CapacityAllocation.php` · `CargoException.php` · `app/Enums/PieceStatus.php` · `TrayLoadStatus.php` |
| **New — services** | The twelve in the table above |
| **New — controllers** | `app/Http/Controllers/Cargo/Admin/DispatchController.php` (board and desk assignment) · `Cargo/Admin/TrayController.php` · `Cargo/Admin/ArrivalController.php` · `Cargo/Portal/PortalPortOpsController.php` (assign, board and unpack on a phone) |
| **New — approvals and jobs** | `app/Services/DualControl/Handlers/TrayLoadHandler.php` · `app/Console/Commands/ParcelBacklogAlerts.php` |
| **New — admin screens** | `resources/views/admin/cargo/dispatch/index.blade.php` · `trays/index.blade.php` · `trays/show.blade.php` · `arrivals/index.blade.php` · `partials/expected-sailing-chip.blade.php` · `resources/views/cargo/labels.blade.php` · `resources/views/cargo/tray-manifest.blade.php` |
| **New — sales portal** | `resources/js/portal/views/port/AssignView.vue` · `TrayLoadView.vue` · `BoardingView.vue` · `UnpackView.vue` · `components/port/ScanInput.vue` · `components/port/SpaceLeft.vue` · `components/booking/RouteStep.vue` · `components/booking/ExpectedSailingChip.vue` · `stores/port.js` · `utils/scanQueue.js` |
| **New — tests** | `tests/Feature/ParcelReceivingTest.php` · `TrayFlowTest.php` · `AssignmentTest.php` · `DispatchBoardTest.php` · `BoardingDepartureTest.php` · `ArrivalUnpackTest.php` · `CapacityLedgerTest.php` · `LateCollectionChargeTest.php` · `tests/Unit/ExpectedSailingEstimatorTest.php` |
| **Changed — services** | `app/Services/Cargo/CargoBookingService.php` · `CargoFareCalculator.php` (priced by route) · `CargoFareService.php` · `CargoInventoryService.php` (holds removed) · `CargoOperationsService.php` · `CargoTrackingService.php` · `CargoManifestService.php` · `CargoHandoverService.php` · `CargoPaymentService.php` (a second payment for the late-collection charge) · `CargoReceiptService.php` · `PublicTrackingService.php` · `CargoDashboardService.php` · `CargoReportService.php` · `CloseOfBusinessService.php` · `app/Services/SmsNotifier.php` · `app/Support/CargoUi.php` |
| **Changed — controllers and routes** | `Cargo/Concerns/BuildsCargoCart.php` (departure list, hold and release removed; quote and receipt work from the route) · `Cargo/CargoPaymentController.php` · `Cargo/Admin/CargoFareController.php` · `Cargo/Admin/AdminCargoController.php` · `Cargo/Admin/CargoOperationsController.php` · `Cargo/Admin/JourneyController.php` · `Cargo/Portal/PortalCargoController.php` · `Cargo/Scanner/ScannerCargoController.php` · `app/Http/Controllers/Admin/SystemConfigController.php` (the late-collection settings) · `routes/web.php` · `routes/api.php` · `routes/console.php` |
| **Changed — approvals and jobs** | `config/dual_control.php` · `Handlers/CargoJourneyStatusHandler.php` · `Handlers/CargoBookingCancelHandler.php` · `Handlers/CargoCapacityHandler.php` · `app/Console/Commands/AutoTransitCargo.php` · `AutoArrivedCargo.php` · `MarkUnclaimedCargo.php` · `RecountCargoInventory.php` |
| **Changed — models** | `app/Models/Consignment.php` · `CargoItem.php` · `CargoType.php` · `CargoInventory.php` · `CargoCapacity.php` · `TransportService.php` · `TrackingEvent.php` · `CargoPayment.php` |
| **Changed — admin screens** | `resources/views/admin/cargo/booking.blade.php` · `bookings/show.blade.php` · `bookings/partials/handover.blade.php` · `bookings/_payment-panel.blade.php` · `consignments.blade.php` · `manifest.blade.php` · `journeys/index.blade.php` · `journeys/availability.blade.php` · `dashboard.blade.php` · `partials/type-form.blade.php` · `partials/fare-editor.blade.php` and `fares.blade.php` (vessel selector removed) · `partials/ui.blade.php` · `transport-units/create.blade.php` · `transport-units/edit.blade.php` · `resources/views/admin/reports/cargo/manifest-pdf.blade.php` · `resources/views/cargo/receipt.blade.php` · `resources/views/cargo/tracking/index.blade.php` · `resources/views/admin/settings/index.blade.php` (the "once or each further day" choice) |
| **Changed — sales portal** | `resources/js/portal/views/cargo/CargoBooking.vue` · `CargoBookingsListView.vue` · `CargoBookingDetailView.vue` · `views/DashboardView.vue` · `components/booking/CargoLines.vue` · `OrderSummary.vue` · `ReviewPay.vue` · `StepPills.vue` · `components/cargo/HandoverModal.vue` · `components/cargo/PaymentPanel.vue` · `components/layout/SidebarNav.vue` · `components/ui/StatusBadge.vue` · `stores/cargo.js` · `router/index.js` · `utils/cargoUi.js` |
| **Changed — tests** | `tests/Feature/CargoOperationsTest.php` · `CargoInventoryTest.php` · `CargoPaymentFlowTest.php` · `CargoPortalBookingTest.php` · `CargoSmsPolicyTest.php` · `PublicTrackingTest.php` · `CargoAdminScreensTest.php` · `CargoPricingTest.php` · `CargoFareVersioningTest.php` |
| **Changed — set-up** | `database/seeders/CargoSystemConfigSeeder.php` · `CargoPermissionSeeder.php` · `MenuSeeder.php` · `NotificationTemplateSeeder.php` · `CargoTypeSeeder.php` |
| **Deleted** | `app/Models/CargoLock.php` · `app/Console/Commands/ClearExpiredCargoLocks.php` · `resources/js/portal/components/booking/DepartureList.vue` · the settings `cargo_hold_minutes_walkin`, `cargo_hold_minutes_agent`, `cargo_hold_minutes_corporate` |

**Conversion of existing bookings.** See section 7.

**Exit criteria.** The acceptance criteria for the parcel flow in SRS section 9. No sailing is ever assigned beyond its limits, including with several assigners at once. Boarding refuses anything not assigned to the sailing and never changes the space held. A missed or cancelled sailing releases its holds and lists the items for reassignment. A recount from the ledger equals the counters for every journey. Two simultaneous scans of one piece give one success. The dispatch board opens within two seconds with 5,000 seeded pieces. The late-collection charge follows example K at 10% and charges nothing at 0%.

---

### Phase 3 — Customers, quotations and contracts

**Goal.** SRS 4.7 to 4.9, VSL-01, VSL-02, ADM-03.

**What you will see.** A Commercial menu: Customers, Quotations (pipeline, builder, revision comparison), Contracts, Cargo bookings, Vessel calendar, Rate cards. Vessel records gain class and limits. Quotations are sent as PDF by email.

**New services** (`app/Services/Cargo/Commercial/`)

| Service | Responsibility |
|---|---|
| `CustomerService` | Accounts, contacts, duplicate check, timeline |
| `RateCardService` | Versioned, dated rates with a floor (same pattern as `CargoFareService`) |
| `QuotationService` | Draft, price, revise, submit, approve, send, accept, decline, expire |
| `QuotationPricing` | Lines from the rate card, floor and authority checks |
| `FeasibilityService` | Vessel limits and calendar conflicts |
| `VesselCalendarService` | Entries, tentative holds, conflict rule |
| `ContractService` | Contract from a quotation, amendments, cancellation, bookings |
| `AcceptanceService` | One-time code to the customer's signatory (reuses the hand-over mechanism) |
| `CommercialDocuments` | Quotation and contract PDFs |

| | Files |
|---|---|
| **New — database** | `…_create_customers_tables.php` · `…_create_rate_cards_table.php` · `…_create_terms_templates_table.php` · `…_create_quotations_tables.php` · `…_create_cargo_contracts_tables.php` · `…_create_cargo_bookings_table.php` · `…_create_vessel_calendar_entries_table.php` · `…_vessel_cargo_particulars.php` · `…_commercial_setup.php` |
| **New — models and enums** | `app/Models/Customer.php` · `CustomerContact.php` · `RateCard.php` · `TermsTemplate.php` · `Quotation.php` · `QuotationLine.php` · `CargoContract.php` · `ContractAmendment.php` · `CargoBooking.php` · `VesselCalendarEntry.php` · `app/Enums/ContractType.php` · `VesselUse.php` · `QuotationStatus.php` · `ContractStatus.php` · `CargoBookingStatus.php` |
| **New — services** | The nine in the table above |
| **New — controllers** | `app/Http/Controllers/Cargo/Admin/CustomerController.php` · `QuotationController.php` · `ContractController.php` · `CargoBookingController.php` · `VesselCalendarController.php` · `RateCardController.php` · `app/Http/Controllers/Cargo/DocumentVerifyController.php` (public check of a document code) |
| **New — approvals, mail, jobs** | `Handlers/QuotationHandler.php` · `Handlers/ContractHandler.php` · `Handlers/RateCardHandler.php` · `Handlers/CustomerCreditHandler.php` · `app/Mail/QuotationMail.php` · `app/Console/Commands/ExpireQuotations.php` |
| **New — admin screens** | `resources/views/admin/cargo/customers/{index,show,form}.blade.php` · `quotations/{index,builder,show,compare}.blade.php` · `contracts/{index,show}.blade.php` · `cargo-bookings/{index,show}.blade.php` · `vessel-calendar/index.blade.php` · `rate-cards/index.blade.php` · `resources/views/cargo/documents/{quotation,contract,_header,_styles}.blade.php` · `resources/views/emails/quotation.blade.php` · `resources/views/cargo/verify.blade.php` |
| **New — tests** | `tests/Feature/CustomerAccountsTest.php` · `QuotationFlowTest.php` · `QuotationPricingTest.php` · `FeasibilityTest.php` · `VesselCalendarTest.php` · `ContractFlowTest.php` |
| **Changed** | `app/Models/TransportUnit.php` · `CargoCapacity.php` · `Consignment.php` · `app/Http/Controllers/Cargo/Admin/TransportUnitController.php` · `resources/views/admin/cargo/transport-units/{create,edit,show}.blade.php` · `config/dual_control.php` · `app/Services/SmsNotifier.php` · `routes/web.php` · `routes/console.php` · `database/seeders/CargoPermissionSeeder.php` · `MenuSeeder.php` · `NotificationTemplateSeeder.php` |

**Exit criteria.** The commercial acceptance criteria in SRS section 9. A quotation can go from draft to contract with every step recorded and its PDF produced.

---

### Phase 4 — Invoicing and receivables

**Goal.** SRS 4.15, the document part of 4.16, CUS-05, PRC-12.

**What you will see.** A Finance menu: Invoices, Receipts, Credit notes, Statements and ageing, Tax codes. A deposit invoice appears when a contract is confirmed. A credit chip on each customer.

**New services** (`app/Services/Cargo/Finance/`)

| Service | Responsibility |
|---|---|
| `InvoiceService` | Draft from payment terms, booking or manual lines; approve, issue, void |
| `CreditNoteService` | Credit notes against an invoice; refunds |
| `ReceiptService` | Record, confirm, allocate, reverse; exchange difference |
| `StatementService` | Statement, ageing, overdue reminders |
| `TaxService` | Tax per line from the tax code and the customer's status |
| `CreditControlService` | Limit and overdue checks; override through approval |
| `ParcelAccountBilling` | Periodic invoice for parcels received on account |
| `FiscalGateway` (interface) and `NullFiscalGateway` | The connection point for the fiscal service; does nothing until configured |
| `FinanceDocuments` | Invoice, credit note, receipt and statement PDFs |

| | Files |
|---|---|
| **New — database** | `…_create_tax_codes_table.php` · `…_create_invoices_tables.php` · `…_create_customer_receipts_tables.php` · `…_add_finance_refs_to_transactions.php` · `…_finance_setup.php` |
| **New — models and enums** | `app/Models/TaxCode.php` · `Invoice.php` · `InvoiceLine.php` · `CustomerReceipt.php` · `ReceiptAllocation.php` · `app/Enums/InvoiceStatus.php` · `InvoiceKind.php` |
| **New — services** | The nine in the table above |
| **New — controllers** | `app/Http/Controllers/Cargo/Admin/InvoiceController.php` · `ReceiptController.php` · `StatementController.php` · `TaxCodeController.php` |
| **New — approvals, mail, jobs** | `Handlers/InvoiceHandler.php` · `Handlers/ReceiptHandler.php` · `app/Mail/InvoiceMail.php` · `app/Mail/StatementMail.php` · `app/Console/Commands/InvoiceReminders.php` · `app/Console/Commands/BillParcelAccounts.php` |
| **New — screens** | `resources/views/admin/finance/invoices/{index,show,form}.blade.php` · `receipts/{index,show,form}.blade.php` · `statements/{index,show}.blade.php` · `tax-codes/index.blade.php` · `resources/views/cargo/documents/{invoice,credit-note,receipt,statement}.blade.php` · `resources/views/emails/{invoice,statement}.blade.php` · `resources/views/components/admin/credit-chip.blade.php` |
| **New — tests** | `tests/Feature/InvoiceLifecycleTest.php` · `ReceiptAllocationTest.php` · `CreditNoteTest.php` · `CrossCurrencySettlementTest.php` · `StatementAgeingTest.php` · `CreditControlTest.php` |
| **Changed** | `app/Services/SystemTransactionService.php` · `app/Services/ExchangeRateService.php` (rate on a given date for documents) · `app/Services/Cargo/Commercial/ContractService.php` · `app/Services/Cargo/CargoPaymentService.php` and `Cargo/Concerns/BuildsCargoCart.php` (the "on account" channel) · `app/Services/Cargo/CargoReportService.php` · `app/Models/SystemTransaction.php` · `app/Models/Customer.php` · `resources/views/admin/cargo/customers/show.blade.php` · `contracts/show.blade.php` · `config/dual_control.php` · `routes/web.php` · `routes/console.php` · `database/seeders/CargoTransactionCategorySeeder.php` · `CargoPermissionSeeder.php` · `MenuSeeder.php` · `NotificationTemplateSeeder.php` |

**Exit criteria.** The invoicing acceptance criteria in SRS section 9, including example J. Every invoice, credit note and receipt has a matching ledger entry.

---

### Phase 5 — Voyages, cargo operations and laytime

**Goal.** SRS 4.10 to 4.12, 4.17, CON-03, CON-06, CON-07.

**What you will see.** Voyages can be created outside a schedule and marked exclusive. A voyage workspace with load plan, tally, port record, laytime clock, checklist and documents. Phone screens for the Master and for check-in. Laytime statements with approval, leading to a demurrage invoice or a despatch credit note.

**New services**

| Service | Responsibility |
|---|---|
| `Voyage/VoyageService` | Ad-hoc voyages, vessel nomination, status, delays |
| `Voyage/ContractBookingService` | Booking lifecycle, reservation on the ledger, over and under shipment |
| `Voyage/CheckInService` | Verify units against the cargo list; tolerance check |
| `Voyage/TallyService` | Load and discharge tally |
| `Voyage/SafetyService` | Limits, dangerous-goods rules, holds, the departure checklist gate |
| `Voyage/VoyageDocuments` | Manifest, waybill, dangerous-goods list, delivery note, NOR |
| `Laytime/LaytimeTerms` | The terms as a value object, read from the booking |
| `Laytime/LaytimeCalculator` | The rules in SRS 5.4; no database access |
| `Laytime/PortRecordService` | Port calls, NOR, events, overrides |
| `Laytime/LaytimeClock` | Live remaining time and accruing amount; alerts |
| `Laytime/LaytimeStatementService` | Versions, approval, link to invoice or credit note |

| | Files |
|---|---|
| **New — database** | `…_voyages_adhoc_and_exclusive.php` · `…_cargo_bookings_laytime_terms.php` · `…_create_port_calls_and_events_tables.php` · `…_create_laytime_statements_table.php` · `…_create_voyage_checklists_table.php` · `…_create_public_holidays_table.php` · `…_cargo_units_on_items.php` · `…_voyage_setup.php` |
| **New — models and enums** | `app/Models/PortCall.php` · `PortEvent.php` · `LaytimeStatement.php` · `VoyageChecklist.php` · `PublicHoliday.php` · `app/Enums/PortEventCode.php` · `LaytimeStatementStatus.php` |
| **New — services** | The eleven in the table above (`app/Services/Cargo/Voyage/`, `app/Services/Cargo/Laytime/`) |
| **New — controllers** | `app/Http/Controllers/Cargo/Admin/VoyageController.php` · `VoyageWorkspaceController.php` · `PortRecordController.php` · `LaytimeController.php` · `Cargo/Portal/PortalVoyageController.php` |
| **New — approvals, jobs** | `Handlers/LaytimeStatementHandler.php` · `Handlers/VoyageHandler.php` · `app/Console/Commands/LaytimeAlerts.php` |
| **New — admin screens** | `resources/views/admin/cargo/voyages/{create,show}.blade.php` · `voyages/partials/{load-plan,tally,port-record,laytime-clock,checklist,documents}.blade.php` · `laytime/{index,show}.blade.php` · `resources/views/components/admin/laytime-chip.blade.php` · `resources/views/cargo/documents/{nor,sof,laytime-statement,waybill,dg-list,delivery-note}.blade.php` |
| **New — sales portal** | `resources/js/portal/views/port/VoyageView.vue` · `PortRecordView.vue` · `ChecklistView.vue` · `CheckInView.vue` · `components/port/LaytimeChip.vue` |
| **New — tests** | `tests/Unit/LaytimeCalculatorTest.php` (every example and table in SRS 5.4 — rates per day and per hour, part units pro rata and in full, despatch as a percentage, as its own rate and as none — and at least twenty further cases) · `tests/Feature/PortRecordTest.php` · `LaytimeStatementFlowTest.php` · `ExclusiveSharedCapacityTest.php` · `AdHocVoyageTest.php` · `CheckInTallyTest.php` · `DepartureChecklistTest.php` |
| **Changed** | `app/Models/TransportService.php` · `Schedule.php` · `CargoInventory.php` · `CargoItem.php` · `CargoBooking.php` · `app/Services/Cargo/CapacityLedger.php` · `CargoInventoryService.php` · `CargoManifestService.php` · `CargoOperationsService.php` · `app/Services/Cargo/Finance/InvoiceService.php` · `CreditNoteService.php` · `app/Console/Commands/GenerateTransportServices.php` · `app/Http/Controllers/Cargo/Admin/JourneyController.php` · `Cargo/Admin/StationController.php` · `resources/views/admin/cargo/journeys/index.blade.php` · `manifest.blade.php` · `stations/{create,edit,index}.blade.php` · `cargo-bookings/show.blade.php` · `resources/js/portal/router/index.js` · `components/layout/SidebarNav.vue` · `config/dual_control.php` · `app/Services/SmsNotifier.php` · `routes/web.php` · `routes/console.php` |

**Exit criteria.** The voyage and laytime acceptance criteria in SRS section 9. Examples F, G and H give the stated results. A statement recomputed from stored events and terms equals the stored statement.

---

### Phase 6 — Time charter and project contracts

**Goal.** SRS 4.13, 4.14, VSL-06, TAR-15.

**What you will see.** On a time-charter contract: the hire period, off-hire events and hire invoices raised by the scheduler. On a project contract: its rates, call-offs and progress against the commitment. Call-offs and account parcels are priced from contract rates.

| | Files |
|---|---|
| **New — database** | `…_create_hire_periods_tables.php` · `…_create_contract_rates_table.php` · `…_charter_project_setup.php` |
| **New — models** | `app/Models/HirePeriod.php` · `OffHireEvent.php` · `ContractRate.php` |
| **New — services** | `app/Services/Cargo/Commercial/HireService.php` · `ProjectContractService.php` · `ContractRateService.php` |
| **New — controllers, approvals, jobs** | `app/Http/Controllers/Cargo/Admin/HireController.php` · `CallOffController.php` · `Handlers/OffHireHandler.php` · `app/Console/Commands/RaiseHireInvoices.php` |
| **New — screens** | `resources/views/admin/cargo/contracts/partials/{hire,off-hire,call-offs,commitment,rates}.blade.php` · `resources/views/cargo/documents/hire-statement.blade.php` |
| **New — tests** | `tests/Feature/TimeCharterTest.php` (example I) · `ProjectContractTest.php` · `ContractRatePrecedenceTest.php` |
| **Changed** | `app/Services/Cargo/CargoFareCalculator.php` (contract rate first) · `Commercial/ContractService.php` · `Commercial/VesselCalendarService.php` · `Finance/InvoiceService.php` · `app/Console/Commands/GenerateTransportServices.php` (skip vessels on charter) · `app/Services/Cargo/CargoReportService.php` · `resources/views/admin/cargo/contracts/show.blade.php` · `config/dual_control.php` · `routes/web.php` · `routes/console.php` |

**Exit criteria.** Example I gives the stated hire and credit. A vessel on time charter has no scheduled sailings generated in the period. A call-off is priced from the contract.

---

### Phase 7 — Insights and options

Each item is small and independent. Choose the ones wanted; each gets its own short plan.

| Item | SRS | Effort |
|---|---|---|
| Management dashboard for both lines | RPT-07 | 3–4 days |
| Choosing the sailing at the counter for a cargo type, such as a vehicle, using the same assignment and space check | PRC-06 | 3–4 days |
| Weight or volume, "whichever is greater" | SRS section 10 | 1–2 days |
| Voyage estimate and voyage result | QUO-14, RPT-08 | 4–5 days |
| Vessel certificates with expiry block | VSL-09 | 2–3 days |
| Incident and damage reporting with a DPA view | SAF-08 | 3–4 days |
| Tide windows per landing site | VSL-08 | 2–3 days |
| Fiscal-service connection | INV-10 | depends on the specification |
| Pay on collection; advance registration | PRC-13, PRC-14 | 3–4 days each |
| Customer portal | CUS-08 | 8–10 days |
| Empty return legs offered for sale | VSL-10 | 2 days |

---

## 6. Flows and processes that change

| Flow | Today | After | Phase |
|---|---|---|---|
| Counter booking | Departure → cargo → people → pay | Destination → parcels → people → pay, for every cargo type. The departure step is removed. | 2 |
| Pricing a line | One basis by the type's unit, plus the fare's value percentage | Sum of the type's bases (CBM, weight, declared value, per piece) never less than the fare's flat price, and nothing else: no surcharges. TZS or USD. | 1 |
| Editing a cargo type | Unit chosen from a list | Charge bases ticked in any combination; approval previews the fares that need rates | 1 |
| Editing a fare | One basis rate required; all rate fields shown | Only the amounts of the type's bases are shown, and each is required; the minimum becomes an optional flat price | 1 |
| Surcharges | Fragile 5%, dangerous-goods fee and livestock % added by the calculator | Removed; a fragile, livestock or hazardous item is its own cargo type | 1 |
| Fare for one vessel | Offered in the fare editor | Removed: the vessel is not known when a parcel is received. No such fare is in use. | 2 |
| Price moment | When the space was held | When the quote on screen was produced, honoured for 15 minutes | 2 |
| Holding space | Held on the departure for 10–15 minutes while booking | Held when the assigner puts a parcel in a tray opened for a sailing, or assigns a big parcel to it; released when it is removed or moved, or the sailing leaves without it or is cancelled | 2 |
| After payment | Booking becomes "booked" | Parcel becomes "received" at the clerk's station | 2 |
| Loading | Tick or scan bookings on the manifest | The assigner scans parcels into trays for a sailing and assigns big parcels to it; the port officer scans trays and big parcels at the vessel, which only marks them boarded | 2 |
| Departure | Loaded bookings go in transit; others are flagged left behind and stay on that journey | Boarded items go in transit; anything assigned but not boarded has its space released and is listed for the assigner to move to a later sailing | 2 |
| Cancelling a sailing | The journey's status changes; its bookings stay on it and can no longer be loaded | Everything assigned to it is released and can be moved to another sailing in one action | 2 |
| Arrival | All bookings become collectable at once | Trays arrive, are unpacked with a discrepancy check, then pieces are ready | 2 |
| Hand-over | Whole booking | Whole booking, or the pieces that have arrived. When the late-collection charge applies, it is paid or waived first. | 2 |
| Uncollected parcels | Marked unclaimed after 7 days | Also a late-collection charge after the grace period (3 days), when the percentage in settings is above 0 | 2 |
| Cancellation | Releases the booking's space on its departure | Releases the space held by any of its assigned pieces | 2 |
| Unpaid auto-cancel (`cargo:payments-sweep`) | Cancels and releases space | Cancels; there is no space to release. An unpaid late-collection attempt expires without touching the parcel. | 2 |
| Capacity totals | Counters updated directly | Counters updated through the ledger; recount rebuilds from the ledger | 2 |
| Auto transit and auto arrive commands | Move bookings by journey status | Move trays and pieces, then roll up | 2 |
| Public tracking and SMS | Booked, loaded, on the way, arrived, collected | Received, on board, on the way, ready for collection, collected; expected sailing shown | 2 |
| Reports, dashboard, shift summary, close of business | TZS totals; booking statuses | Totals per currency; new statuses; new parcel reports | 1, 2 |
| Journey generation | Every active schedule, every day | Skips vessels blocked by a firm calendar entry | 5, 6 |
| Journey creation | Only from schedules | Also created directly as charter or ad-hoc voyages | 5 |
| Vessel set-up | Seats, lane metres, kg, CBM | Class, limits, charterable | 3 |
| Ledger posting | Counter payments only | Also invoices, credit notes and customer receipts | 4 |
| Approvals | 70 operations | About 20 more (SRS appendix B) | 2–6 |
| Scheduler | 11 entries | Removed: `voyage:generate`, `cargo:clear-locks`. Added: backlog alerts, collection reminders, quotation expiry, invoice reminders, parcel account billing, laytime alerts, hire invoices. | 0–6 |

---

## 7. Conversion of existing data

| Data | Conversion | Phase |
|---|---|---|
| Cargo types | Charge bases from the current unit; value basis where live fares charge on value and no bookable pair is lost | 1 |
| Booked lines | `unit_charge`, `weight_charge` or `volume_charge` filled from `base_fare` according to the line's pricing method | 1 |
| Consignment status | `booked` → `received`, `loaded` → `boarded`, `delivered` → `arrived`; others unchanged. Tracking events keep their original text and gain nothing. | 2 |
| Consignment stations | Origin and destination from the route; received station from the origin; received time from the payment time | 2 |
| Pieces | One piece per unit of quantity on each line (26 lines today), each with a new label. Existing line labels keep working as the label of piece 1. | 2 |
| Sailing link | Existing bookings keep their sailing; pieces inherit it | 2 |
| Capacity | One ledger entry per existing booking on a sailing (held if not yet loaded, confirmed if loaded), so counters and ledger agree from the first day. Open counter holds are released before `cargo_locks` is dropped. | 2 |
| Surcharges | Existing lines keep their price and snapshot. New parcels of fragile and hazardous types are priced by their fares only. | 1 |
| Users | Duty station left empty; set by an administrator | 0 |
| Vessels | Class set to passenger ferry; limits entered by operations | 3 |

Every conversion is in the migration's `up()` with the reverse in `down()`. The 25 bookings are test data, so a simpler alternative is to archive them and start clean; say if you prefer that.

---

## 8. Testing and quality

### 8.1 Levels

| Level | What | Where |
|---|---|---|
| Fixed calculation cases | Every example in SRS section 5, plus edge cases: zero and missing values, bands at their boundaries, charges just below and just above the flat price, each of the seven charge combinations, a sailing filled to exactly its limit, laytime across Sundays and holidays, reversible laytime, stoppages after expiry, demurrage and despatch per day and per hour, the last free day for collection | `tests/Unit` |
| Feature tests | Each flow through its real endpoints in both portals, with permissions and approvals | `tests/Feature` |
| Concurrency tests | Two processes taking the same space, scanning the same piece, taking a document number | `CapacityLedgerTest`, `DocumentNumberingTest` |
| Regression | The existing safe cargo suites, run before and after each phase | `cargo` test suite |
| Screen checks | Each new or changed screen driven in a browser at desktop and phone width, with screenshots in the walkthrough | Scripted |
| Your testing | The walkthrough steps for each phase, on local and then on UAT | You |

### 8.2 Rules

- Tests run only on `uclp_cargo_test`. SMS, email and payment gateways are blocked in tests, as today.
- Every new test file uses transactions, so nothing it writes remains.
- A phase is not offered for acceptance with a failing test. If a test cannot be made to pass, that is reported with the output.

### 8.3 Checks specific to this work

| Risk to correctness | Check |
|---|---|
| A price changes by accident | Lines priced before and after Phase 1 from the same input give the same total for single-basis types |
| Space is lost or double counted | After each parcel and voyage test, a recount from the ledger equals the counters |
| A parcel is lost between stages | Every piece of a consignment is in exactly one stage, and the consignment's counters equal the count of its pieces |
| A laytime result cannot be explained | Each statement stores its periods; recomputing from events gives the same result |
| An invoice number repeats or skips | Parallel issue test |
| A frozen document is changed | Attempts to update a sent quotation, issued invoice or approved statement are refused |
| A receiver is charged wrongly for late collection | Fixed cases on each side of the end of the grace period, at 0% and above, charged once and for each further day, paid and waived |
| A sailing is overfilled | After every assignment test, the space held and boarded on each sailing is within its limits, including when several assigners work at once; boarding never changes the amounts held |

---

## 9. Risks

| # | Risk | Likelihood | Effect | Response |
|---|---|---|---|---|
| R1 | The programme is large and requirements shift during it | High | Delay | Phases that each stand alone; decisions D-01 to D-10 settled before Phase 2; a short revised plan at the start of each phase if anything changed |
| R2 | Renaming statuses breaks a report, SMS or screen | Medium | Wrong status shown | Statuses defined once in Phase 0; a search for every old value as a checklist item; tests on reports, SMS and tracking |
| R3 | Receiving without a sailing lets the counter accept more than the vessels can carry | Medium | Backlog and complaints | No sailing can be assigned past its limits; backlog chip at the counter, expected sailing on the receipt, the forecast on the dispatch board, ageing alerts, warning when no sailing has room in the horizon |
| R4 | Tray scanning is not adopted at the quay (devices, light, connection, habit) | Medium | The flow is bypassed | Works with a phone camera or a handheld scanner; scans queue offline; one scan per piece; pilot on one route before all |
| R5 | Counters and ledger disagree after the conversion | Low | Wrong free space | Conversion creates entries for every existing booking; recount in dry-run compares before go-live |
| R6 | A customer disputes a laytime calculation | Medium | Demurrage not collected | Terms as structured fields agreed in the quotation; timed events with evidence; countersignature; reproducible versions |
| R7 | Tax and fiscal rules are not confirmed in time | Medium | Invoices not compliant | Tax codes configurable; fiscal connection as a separate item; finance confirms D-19 before Phase 4 |
| R8 | Dual-currency mistakes | Medium | Wrong totals | One money helper; totals always per currency; cross-currency tests |
| R9 | The scheduler or queue worker is not running in production | Medium | Holds, alerts and automatic invoices stop | The existing stale-scheduler banner extended to these jobs |
| R10 | The test database cannot be built from migrations | Medium | Phase 0 slips | Built by copying structure and reference data from the development database |
| R11 | Vessel limits are not supplied | Medium | Feasibility checks cannot run | Quotations on a vessel without limits are flagged as unchecked and need approval |
| R12 | UAT is refreshed by restoring a database dump | Low | UAT differs from local | Each walkthrough includes a fresh dump for UAT; credentials still depend on the application key matching |
| R13 | Passenger-era code is disturbed | Low | Unrelated errors | Not touched, apart from the failing nightly command |
| R14 | Staff need to change how they work | High | Slow uptake | Short guides per role with each phase; the old booking screen stays available behind the switch during the pilot |
| R15 | Receivers dispute the late-collection charge at the counter | Medium | Queues, complaints, waived income | The last free day is printed on the receipt and sent by SMS, with a reminder; a supervisor can waive with a reason; waivers are reported; the charge can be switched off at once |
| R16 | Trays left open, or items assigned and forgotten, hold space other parcels need | Medium | Sailings look full when they are not | Open trays and their age on the dispatch board; an alert near the cut-off; a supervisor can release an assignment; holds end when the sailing departs |
| R17 | Removing the surcharges changes prices for existing fragile and hazardous types | Low | A parcel priced differently than today | Only test data exists; the tariff is re-keyed before go-live (F-15); the differences are shown in the walkthrough |
| R18 | A cancelled sailing, for weather, leaves many trays to reassign at once | Medium | Delays at the warehouse | Everything on a sailing moves to another in one action, within its space, with the next sailing that has room proposed |

---

## 10. How each phase is delivered

Each phase follows the working agreement.

1. **Plan.** This document, approved on 15 November 2026. Each phase runs in its own session, started from the task tracker ([04_TASK_TRACKER.md](04_TASK_TRACKER.md)). If the code or a decision has changed since, the session submits a short revised plan for its phase, with a list of changes, and waits for approval.
2. **Tracker.** One item per step: migrations, each service, each screen, tests, build, walkthrough.
3. **Baseline.** `git status` of `cargo` recorded; any file with uncommitted changes backed up to `book/.claude/backups/<phase>/`; a list of new files kept; `pg_dump` of `uclp_cargo` before migrations.
4. **Implementation.** Each file shown after it is created or edited, with a one-line summary.
5. **Walkthrough.** What was done, every file and migration, and test steps with expected results for each screen and flow.
6. **Accept or reject.** Nothing is committed until you accept. On rejection, migrations are rolled back, the database restored from the dump if needed, new files deleted, changed files restored, and the tree compared with the baseline. On acceptance, only the phase's files are staged for you to commit, and the backups are deleted.

The sales portal needs `npm run build` after each phase that changes it, since `public/build` is not in the repository.

---

## 11. What is needed to start

| For | Needed |
|---|---|
| Phase 0 | Nothing. The plan, including the test database `uclp_cargo_test`, is approved. |
| Phase 1 | Nothing. |
| Phase 2 | Nothing. Management can set the late-collection percentage in settings at any time. |
| Phase 3 | The vessel particulars (D-14) from operations, and the cancellation fee percentages (D-17) from management. |
| Phase 4 | From finance: the tax treatment (D-19), and the invoice numbering, company legal details and bank accounts (D-20). |
| Phase 5 | The list of landing sites, and which depend on the tide (D-23), from operations. |
| Phase 6 | Nothing. |

Every other decision in document 02, section 11, was accepted with the plan.

---

## 12. Changes since the last version

Version 1.3 is the approved plan. You approved version 1.2 on 15 November 2026 with one change, the flat price (D-30):

| Change | Where |
|---|---|
| The fare's minimum charge becomes an optional flat price, the least a line pays, in the existing `min_charge` columns | AD-05, Phase 1, section 6 |
| Rate bands apply each band's rate only to the quantity within it, so a heavier parcel never costs less than a lighter one | Phase 1, section 8 |
| Status set to approved; section 11 lists only the information still to be supplied; each phase runs in its own session from [04_TASK_TRACKER.md](04_TASK_TRACKER.md) | Header, sections 10 and 11 |

Version 1.2 answered your second comments of 15 November 2026 on version 1.1:

| # | Your comment | Change to the plan |
|---|---|---|
| 1 | Hold and calculate space when parcels go into the tray; big parcels are assigned a ferry the same way, without the tray | AD-13 rewritten: the assigner opens a tray for a sailing, and every parcel scanned in holds its space there; big parcels and vehicles are held the same way without a tray. New `AssignmentService`, `AssignView.vue`, `SpaceLeft.vue` and `AssignmentTest`. `cargo_pieces` gains `assigned_at`, `assigned_by` and `missed_sailings`; `tray_loads` gains `missed_sailings`; the ledger's "held" state now means assigned. |
| 2 | The assigner assigns parcels to the tray and the tray to the ferry; boarding only changes the status, to avoid overboarding | `BoardingService` becomes a status change that refuses anything not assigned to the sailing. Departure and cancellation release what was assigned but not boarded and list it for reassignment. A whole sailing's load can move to another sailing in one action. Risks R16 and R18 added. |
| 3 | The cargo type's tick boxes decide which amounts the fare requires, and all are calculated | Phase 1 removes the fragile, livestock and dangerous-goods surcharges and their settings, and stops the minimum charge (decision D-30 asks whether to keep it). The express and fuel options leave Phase 7, and `consignments.service_level` is dropped. Risk R17 updated. |
| 4 | The percentage is set in system settings, 0 means no charge; add a grace period setting | The on/off setting is dropped; `cargo_late_collection_free_days` becomes `cargo_late_collection_grace_days`. The values in force when a parcel is received are copied onto it. |
| 5 | Per piece kept for now | No change. |

Version 1.1 took in your first comments: charge bases in any combination, one receive-first flow for every cargo type, demurrage and despatch as each contract states, and the late-collection charge.

Phase 2 stays at 22–28 days but moves towards the upper end, because assignment brings back work that version 1.1 had removed. Removing the surcharges trims Phase 1 slightly.
