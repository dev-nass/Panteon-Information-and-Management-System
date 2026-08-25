# Plan: Admin Dashboard — Summary Tab Improvements (Demographics, Geography, Global Filters)

## Goal
Enhance the Admin Dashboard **Summary** tab with demographic and geographic charts that,
together with a new Filters modal, act as global filters for the dashboard. The year
selector should only appear when the "Yearly" filter is active.

---

## 1. Current State

| Piece | Location | Notes |
|---|---|---|
| View | `resources/js/Pages/Admin/DashboardView.vue` | Tabs: summary/phases/clusters; filter pills + year select always visible |
| Controller | `app/Http/Controllers/Admin/DashboardController.php` | Reads `tab`, `filter`, `year`, `phase_id`, `cluster_type`, `cluster_page` |
| Service | `app/Services/DashboardService.php` | `getTotalStats()`, `getDisposalStats()`, `getActivityData()` |
| Data source | `deceased_records` JOIN `burial_records` | Activity uses `date_of_depository` |

### Data reality (21,205 deceased records)
- `age`: 98 filled · `date_of_birth`: 97 → demographics will be sparse; use COALESCE fallback
- `address`: 12,333 filled (barangay-level free text: "SAMPALOC IV" / "Sampaloc 4")
- `civil_status`, `religion`, `nationality`: 0 filled · **no gender column exists anywhere**
- Decision (user): skip gender; normalize addresses in query/service; age col w/ DOB fallback

---

## 2. Backend Changes

### 2.1 `DashboardService`
1. **Filter contract** — single associative array used by all summary queries:
   ```php
   ['year' => ?int, 'age_range' => ?string, 'barangay' => ?string]
   ```
   - `year` → `whereYear('deceased_records.date_of_depository', $year)`
   - `barangay` → normalized address match
   - `age_range` → computed-age between bounds
2. **Computed age SQL** (shared expression):
   `COALESCE(age, TIMESTAMPDIFF(YEAR, date_of_birth, date_of_death))`
3. **Address normalization helper** (`normalizeBarangay(?string): ?string`):
   lowercase, trim, strip `brgy.`/`barangay` prefix, unify ordinal forms
   (`iv ↔ 4`, `iii ↔ 3`, `ii ↔ 2`, `i ↔ 1`), collapse spaces.
4. New methods:
   - `getAgeDistribution(array $filters): array` — buckets:
     `0–12, 13–19, 20–39, 40–59, 60–74, 75+, Unknown` → `{labels[], values[]}`
   - `getGeographicDistribution(array $filters, int $limit = 10): array` —
     normalized GROUP BY address, top N + `Others` → `{labels[], values[]}`
   - `getFilterOptions(): array` — distinct normalized barangays (for modal select)
5. Refactor `getTotalStats()`, `getDisposalStats()`, `getActivityData()` to accept
   `$filters` so stat cards, doughnut, and activity chart all respond to modal filters.

### 2.2 `DashboardController@index`
- Read new params: `age_range`, `barangay` (validate against known buckets/list).
- For `tab === 'summary'`, additionally return:
  `demographic_data`, `geographic_data`, `filter_options`,
  `active_filters` (echo back for UI state).
- Pass merged filters into every summary data call.

---

## 3. Frontend Changes (`Admin/DashboardView.vue`)

1. **Filters button** — shown on Summary tab next to filter pills; opens a Preline
   modal built from existing `Components/Modal.vue` (slots: header/main/footer).
   - New component: `resources/js/Components/Dashboard/DashboardFiltersModal.vue`
   - Contains: Age Range select (buckets + All), Barangay select (from
     `filter_options`), footer Apply / Reset buttons.
2. **Active filter chips** — removable chips under the filter row showing current
   `age_range` / `barangay`.
3. **Global filter behavior** — applying the modal triggers one `router.get` with all
   params; tab/filter/year changes preserve active modal filters in the URL.
4. **Year selector visibility** — wrap in `v-if="activeFilter === 'yearly'"`.
5. **New charts** (Summary tab, second grid row below Activity + Doughnut):
   - Demographics: `BarChart` fed by `demographic_data` ("Age Distribution")
   - Geography: `HorizontalBarChart` fed by `geographic_data` ("Residence by Barangay")
   - Empty-state message when a dataset has zero rows (mirrors clusters empty state).
6. Chart options follow existing style conventions (green palette, no legends where
   single-series).

---

## 4. Tests (Pest — none exist yet for dashboard)

New `tests/Feature/Admin/DashboardTest.php`:
- Summary renders with demographic/geographic props and filter options.
- `age_range` and `barangay` params filter data (seed via existing factories:
  DeceasedRecordFactory/BurialRecordFactory — verify states; create minimal states if absent).
- Year param still works for yearly; unknown `age_range`/`barangay` values ignored safely.
- Service unit tests: normalization mapping (`'PALIPARAN III' === 'Paliparan 3'`),
  age bucketing incl. DOB-fallback and Unknown bucket.

## 5. Verification
- `php artisan test --compact --filter=Dashboard`
- `vendor/bin/pint --dirty --format agent`
- Manual pass: `npm run dev` (or `build`) + click-through of tabs/filters/modal.

## 6. Out of Scope (per decisions)
- Gender field/column (skipped entirely)
- Clerk dashboard changes
- Schema migrations (no new columns)

---

## Open Notes
- **Monthly filter ignores year today** (`DashboardService.php:97-104` uses current
  month) — plan keeps monthly/weekly/today pinned to *now*, with year only affecting
  *yearly*, matching the "year only on yearly" request.
- **Charts reflect all active filters**, including their own dimension (e.g., picking
  barangay "Salawag" makes the geo chart show just that bar). Alternative: each chart
  ignores its own dimension — easy tweak either way.
