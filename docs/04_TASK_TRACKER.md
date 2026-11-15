# Task Tracker — Parcel Service and Contract Cargo

| | |
|---|---|
| **Document** | 04 of 4 |
| **Tracks** | [03 Implementation Plan](03_IMPLEMENTATION_PLAN.md) v1.3, which delivers [02 System Requirements](02_SYSTEM_REQUIREMENTS_DOCUMENT.md) v1.3 |
| **Approved** | 15 November 2026 |
| **Rule** | One session per phase, as you asked on 15 November 2026. Every phase follows the working agreement in `book/CLAUDE.md`. |

## Starting a phase

Open a new Claude Code session in `book/` and say:

> Start Phase 0 of the cargo programme. Follow cargo/docs/04_TASK_TRACKER.md.

Use the next number for later phases. The session then:

1. Reads this tracker, the phase in document 03 and the requirements it names in document 02, then the code the phase touches.
2. If anything has changed since the plan (code, data or a decision), submits a short revised plan for the phase and waits for approval. Otherwise it starts.
3. Records the baseline:
   - the `git status` of `cargo`;
   - a copy in `book/.claude/backups/<phase>/` of any file that already has uncommitted changes;
   - a list of new files;
   - a `pg_dump` of `uclp_cargo` before migrations, made with `/usr/lib/postgresql/14/bin/pg_dump`.
4. Works through the phase's steps below, ticks each one here when it is done, and shows you each file it creates or edits.
5. Runs only the safe cargo test files, on `uclp_cargo_test` once Phase 0 is in. Runs `npm run build` when the sales portal changed.
6. Gives the walkthrough: what was done, every file and migration, and test steps with expected results.
7. Leaves everything uncommitted until you decide:
   - **Accepted:** stages only the phase's files, including this tracker, for you to commit; deletes the backups; marks the phase Accepted here.
   - **Rejected:** rolls back the migrations, restores the backups and the database if needed, deletes the new files, and confirms the tree matches the baseline.

## Status

| Phase | Status | Depends on | Information needed first |
|---|---|---|---|
| Planning: documents 01 to 04 | Approved 15 Nov 2026; staged for you to commit | — | — |
| 0 Foundations | Delivered 15 Nov 2026; awaiting your acceptance | — | — |
| 1 Tariff | Not started | 0 | — |
| 2 Parcel flow | Not started | 0, 1 | — |
| 3 Customers, quotations, contracts | Not started | 0 | Vessel particulars (D-14) from operations; cancellation fee percentages (D-17) from management |
| 4 Invoicing and receivables | Not started | 3 | Tax treatment (D-19); invoice numbering, company legal details and bank accounts (D-20), from finance |
| 5 Voyages, cargo operations, laytime | Not started | 2, 3, 4 | Landing sites and which depend on the tide (D-23), from operations |
| 6 Time charter and project contracts | Not started | 4, 5 | — |
| 7 Options | Not chosen | as chosen | Which options you want |

The late-collection percentage is 0 (no charge) until management sets it in settings, which they can do any time after Phase 2.

## Phase 0 — Foundations

- [x] Baseline recorded; `pg_dump` taken
- [x] `scripts/make-test-db.sh` creates `uclp_cargo_test` from the structure and reference data of `uclp_cargo`
- [x] Tests refuse to run unless the database name ends in `_test`; the password leaves `phpunit.xml`
- [x] Rollback added to `CargoAdminCrudTest`, `CargoBookingFlowTest`, `CargoPermissionsTest` and `CargoPortalAuthTest`; a `cargo` test suite lists the safe files
- [x] Status enums and `ConsignmentStateMachine`, defined but not yet used by the flows
- [x] `Money` helper
- [x] Document numbering (`document_sequences`) and attachments
- [x] Duty station on users: migration, model, user screens
- [x] `voyage:generate` removed from the scheduler
- [x] Service-line switches `parcel_receive_flow_enabled` and `contract_cargo_enabled`, both off
- [x] New tests pass; the cargo suite passes on the test database; development row counts unchanged
- [x] Walkthrough given
- [ ] Accepted or rejected

## Phase 1 — Tariff

- [ ] Baseline recorded; `pg_dump` taken
- [ ] `charge_bases` on cargo types, converted from today's unit; bookable type-and-route pairs identical before and after
- [ ] A charge column per basis on cargo lines; existing lines back-filled
- [ ] Rate bands (`cargo_fare_tiers`), each band's rate applying only within the band
- [ ] USD selling settings and permission; surcharge settings retired
- [ ] Calculator: sum of the type's bases, flat price, bands, TZS or USD, no surcharges
- [ ] Fare service and approval handlers: only the type's amounts required; flat price optional
- [ ] Cargo type form with the tick boxes; type profile
- [ ] Fare editor and route rate card: one field per basis, flat price, bands
- [ ] Booking in both portals asks only for what the type needs; currency switch; price breakdown
- [ ] Receipt, booking page and lists show the breakdown
- [ ] Totals by currency in reports, dashboard, close of business and shift summary
- [ ] Tests: `CargoChargeBasesTest` (examples A to E, all seven combinations) and `CargoUsdSellingTest`; existing safe tests updated
- [ ] `npm run build`
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 2 — Parcel flow

- [ ] Baseline recorded; `pg_dump` taken
- [ ] Migrations:
  - [ ] consignment columns and status conversion
  - [ ] pieces
  - [ ] trays and tray loads
  - [ ] capacity ledger
  - [ ] exceptions
  - [ ] tracking references
  - [ ] `cargo_locks` retired
  - [ ] late-collection charge
  - [ ] set-up: permissions, roles, menus, SMS templates, settings
- [ ] Models and enums
- [ ] Capacity ledger, and recount from the ledger
- [ ] Receiving in both portals: no departure step, priced by route, quote honoured for 15 minutes, piece labels
- [ ] Expected-sailing estimate and chips
- [ ] Tray register
- [ ] Assignment on the phone and at a desk:
  - [ ] a tray is opened for a sailing
  - [ ] parcels are scanned in with their space held
  - [ ] big parcels and vehicles are assigned directly
  - [ ] items can be moved to another sailing
  - [ ] a whole sailing's load can be moved in one action
- [ ] Dispatch board
- [ ] Boarding: status change only; refuses anything not assigned to the sailing
- [ ] Departure and cancellation release what was not boarded; auto-transit and auto-arrive commands updated
- [ ] Arrival and unpacking with discrepancies; ready for collection
- [ ] Hand-over: partial collection; late-collection charge paid or waived
- [ ] Exceptions: hold, damage, shortage, return
- [ ] Status roll-up from pieces; new status names in screens, reports, SMS and public tracking
- [ ] Manifest shows assigned and boarded
- [ ] SMS templates and reminders; backlog alerts command
- [ ] Parcel reports
- [ ] Existing bookings converted (plan section 7)
- [ ] Tests:
  - [ ] new: receiving, trays, assignment, dispatch board, boarding and departure, arrival, ledger, late-collection charge, estimate
  - [ ] existing safe tests updated
- [ ] `npm run build`
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 3 — Customers, quotations, contracts

- [ ] Vessel particulars (D-14) and cancellation fee percentages (D-17) received
- [ ] Baseline recorded; `pg_dump` taken
- [ ] Vessel particulars on vessels; vessel screens
- [ ] Customers and contacts
- [ ] Rate cards
- [ ] Terms templates and clause library
- [ ] Quotations:
  - [ ] builder, priced from rate cards
  - [ ] feasibility checks
  - [ ] approval
  - [ ] revisions
  - [ ] PDF and email
  - [ ] acceptance by signed copy or code
- [ ] Vessel calendar with tentative holds
- [ ] Contracts, amendments and cargo bookings
- [ ] Approvals, permissions, menus and templates registered; quotation expiry job; document check page
- [ ] Tests
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 4 — Invoicing and receivables

- [ ] Tax treatment (D-19) and invoice details (D-20) received from finance
- [ ] Baseline recorded; `pg_dump` taken
- [ ] Tax codes
- [ ] Invoices: lifecycle, numbering, PDF and email
- [ ] Credit notes and refunds
- [ ] Customer receipts and allocation, including payment in the other currency
- [ ] Statements, ageing and reminders
- [ ] Credit control
- [ ] Parcels on account, billed periodically
- [ ] Ledger posting; fiscal connection point, inactive until configured
- [ ] Approvals, permissions, menus and templates registered
- [ ] Tests
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 5 — Voyages, cargo operations, laytime

- [ ] Landing sites listed (D-23)
- [ ] Baseline recorded; `pg_dump` taken
- [ ] Ad-hoc and charter voyages; exclusive and shared capacity
- [ ] Laytime, demurrage and despatch terms on cargo bookings
- [ ] Port calls and events; the Master's phone screens
- [ ] Laytime calculator with its fixed cases (examples F to H and the tables in SRS 5.4)
- [ ] Laytime clock and alerts
- [ ] Laytime statements with approval, raising a demurrage invoice or a despatch credit note
- [ ] Check-in and tally
- [ ] Safety: vessel limits, dangerous goods, departure checklist, holds
- [ ] Voyage documents
- [ ] Tests
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 6 — Time charter and project contracts

- [ ] Baseline recorded; `pg_dump` taken
- [ ] Hire periods and off-hire
- [ ] Hire invoices raised by the scheduler
- [ ] Contract rates take precedence over the tariff
- [ ] Project call-offs and commitments
- [ ] Journey generation skips vessels on charter
- [ ] Tests, including example I
- [ ] Walkthrough given
- [ ] Accepted or rejected

## Phase 7 — Options

Choose from the Phase 7 table in plan section 5. Each chosen option gets its own short plan and session.

## Log

| Date | Phase | Note |
|---|---|---|
| 15 Nov 2026 | Planning | Documents 01 to 04 approved; D-30 settled as a flat price |
| 15 Nov 2026 | 0 | Short revised plan approved: `.gitignore`, a duty-station route, the settings toggles, valid domains on the create-user form, the default `cargo` suite with legacy files skipped, and one committed parallel test |
| 15 Nov 2026 | 0 | Delivered. `php artisan test` runs the `cargo` suite on `uclp_cargo_test`: 181 tests pass. After the development database is migrated in a later phase, run `scripts/make-test-db.sh` again |
| 15 Nov 2026 | 0 | `CargoAdminCrudTest` and `CargoBookingFlowTest` have rollback but are not in the suite: 13 of their 15 tests call endpoints removed before this programme. To be deleted or rewritten, as you decide |
| 15 Nov 2026 | 0 | `CargoInventoryTest` now picks two departures on different routes; it failed at random when it picked two on one route |
