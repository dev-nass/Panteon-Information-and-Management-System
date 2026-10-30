# Shared Lot Refactor — Follow-up Changes

**Status:** Draft  
**Created:** 2026-10-30  
**Related plan:** [`shared-lot-relationship-plan.md`](./shared-lot-relationship-plan.md)  
**Purpose:** Track the code, data, test, documentation, and rollout work that remains after allowing multiple active burial records to share one lot.

---

## 1. Must Do Before Deployment

### 1.1 Verify the schema in every environment

No migration was added because the repository schema already supports shared lots. Confirm production and staging match it before releasing:

```sql
SHOW COLUMNS FROM burial_records;
SHOW INDEX FROM burial_records WHERE Column_name = 'lot_id';
SHOW COLUMNS FROM deceased_records;
```

Expected:

- `burial_records.lot_id` exists.
- The `lot_id` index is **not unique**.
- `deceased_records.lot_id` does **not** exist.

If production has a unique index or a legacy `deceased_records.lot_id`, stop and create a corrective migration/backfill before deploying this code.

### 1.2 Audit existing unassigned burial records

There is intentionally **no automatic backfill**. Records previously imported with `lot_id = null` must be reassigned through the edit screen because the importer did not store the original phase/cluster/apt label.

```sql
SELECT br.id, d.first_name, d.last_name, br.created_at
FROM burial_records br
JOIN deceased_records d ON d.id = br.deceased_record_id
WHERE br.lot_id IS NULL
  AND br.archived_at IS NULL
ORDER BY br.created_at;
```

Operational workflow:

1. Identify active records with `lot_id IS NULL`.
2. Open each burial record and assign the correct lot.
3. Do not re-run the seeder or importer expecting it to repair existing rows.

### 1.3 Fresh-seed smoke test

Run only in local/development:

```bash
php artisan migrate:fresh --seed
```

Then verify shared assignments:

```sql
SELECT lot_id, COUNT(*) AS burial_count
FROM burial_records
WHERE lot_id IS NOT NULL
GROUP BY lot_id
HAVING burial_count > 1
ORDER BY burial_count DESC
LIMIT 20;
```

Also confirm records that were previously skipped now have a lot:

```sql
SELECT COUNT(*) AS unassigned
FROM burial_records
WHERE lot_id IS NULL;
```

### 1.4 Import smoke test

Upload a small file containing two different deceased rows with the same phase/cluster/apt combination.

Expected:

- Both burial records reference the same `lot_id`.
- No “already occupied” warning appears.
- The import error list is empty unless a lot truly cannot be resolved.

### 1.5 Manual QA checklist

- [ ] Clerk can select a lot that already has an active burial record.
- [ ] Clerk can edit a burial record and move it onto an occupied lot.
- [ ] Restoring an archived record keeps its original lot, even when another active record still uses that lot.
- [ ] Admin lot “View Details” opens the burial-record index filtered to that lot and lists every record sharing it.
- [ ] Admin and clerk index filters preserve `lot_id` when changing search, disposal filter, or sort order.
- [ ] The lot filter banner can be cleared without leaving stale state.
- [ ] Phase reports show `available_lots >= 0` and occupancy is based on distinct lots.
- [ ] Dashboard occupied/available lot totals still match the distinct-lot definition.
- [ ] Map lot modal lists every active burial record for a lot.
- [ ] Search suggestions return every matching burial record, including multiple records on one lot.

---

## 2. Code Consistency Still to Address

The core write paths are complete. The following read paths still mix “record count” and “lot count” semantics, or still include archived records.

### 2.1 Exclude archived records from lot-management map lookups

**Files:**

- `app/Http/Controllers/Api/LotManagementSearchController.php`
- `app/Http/Resources/LotResource.php`

`cluster()` and `lot()` eager-load `lots.burialRecords` without `whereNull('archived_at')`. Because archived records keep their `lot_id`, those endpoints can:

- Mark a lot as `occupied` even when only archived records remain.
- Return an inflated `burial_count`.
- Display archived records in the lot modal.

Recommended change:

```php
'lots.burialRecords' => function ($query) {
    $query->whereNull('archived_at');
},
```

Apply the same active-record filter to the single-lot lookup. If archived history should remain visible, split it into a separate `archived_burial_records` payload instead of mixing it into the active count.

### 2.2 Align map occupancy labels and values

**Files:**

- `app/Http/Resources/PhaseResource.php`
- `resources/js/Components/Map/PhaseModal.vue`
- `resources/js/Components/Map/ClusterModal.vue`

Current inconsistencies:

- `PhaseResource::occupants` counts **all burial records**, including archived ones.
- Shared lots mean the record count can exceed the lot/capacity count, so “Occupants” and “Total Capacity” no longer describe the same unit.
- `ClusterModal` labels `occupied_lots` as “Occupants”, but the value is a count of **distinct lots**.

Decide one convention and apply it to the API and modal copy:

- Option A: label record counts as “Burial Records” and exclude archived records.
- Option B: expose `occupied_lots` as “Occupied Lots” and keep active record count as a separate “Burial Records” value.

At minimum:

- Add `whereNull('archived_at')` to `PhaseResource::occupants`.
- Rename `ClusterModal`’s “Occupants” label to “Occupied Lots”.

### 2.3 Add active counts to the edit lot dropdown (optional parity work)

**Files:**

- `app/Repositories/PhaseRepository.php`
- `app/Services/BurialRecordService.php`
- `resources/js/Pages/Shared/BurialRecords/ShowView.vue`

`CreateView.vue` now shows `(N records)` next to occupied lots. The edit form in `ShowView.vue` shows only `column - row`, even though it now permits selecting occupied lots.

If clerk UX should match, add an `active_burial_count` to `getPhasesWithLotsForShow()` and render the same count in the edit dropdown. This is display-only; do not disable the option.

### 2.4 Resolve ambiguity on visitor-map polygon clicks

**File:**

- `resources/js/Pages/VisitorInteractiveMapView.vue`

When a visitor clicks a polygon directly, `openVisitorDeceasedDetailModal()` falls back to the first burial record on the first lot:

```js
feature?.lots?.[0]?.burial_records?.[0]?.burial?.id
```

With shared lots, that silently selects an arbitrary record. Recommended:

- If a lot has multiple active burial records, open the list modal instead of the tailored single-record modal.
- Alternatively, require the visitor to choose from a list before opening the detail view.

Search-result flows already pass a specific `burial_id`, so they are fine.

### 2.5 Distinguish “occupants” from “occupied lots” in Excel export

**File:**

- `app/Http/Controllers/Admin/GenerateReportController.php`

The phase Excel export now has both:

- `Total Occupants` — count of active burial records.
- `Occupied Lots` — count of distinct lots with active records.

That is valid, but the labels should make the distinction explicit. Consider renaming `Total Occupants` to `Total Burial Records` so it is not confused with lot capacity. The PDF already displays only “Occupied” lots.

### 2.6 Re-check `LotResource` status when the relation is not loaded

**Files:**

- `app/Http/Resources/LotResource.php`
- `app/Http/Resources/BurialRecordResource.php`

`LotResource` defaults `burial_count` to `0` when `burialRecords` is not loaded, which makes the lot appear `available`. `BurialRecordResource` constructs `LotResource` from a `lot` relation that does not normally include `burialRecords`.

Audit all consumers. If the status is displayed, either:

- Load an active count before constructing the resource, or
- Make the status field optional when the count is unavailable instead of defaulting to `available`.

### 2.7 Preserve other filters when clearing the lot filter

**File:**

- `resources/js/Pages/Shared/BurialRecords/IndexView.vue`

The “Clear lot filter” banner link points to the bare index route, so it also clears search, disposal, and sort selections. If the intended action is “remove only the lot constraint”, build the link with the existing `search`, `filter`, `sort_field`, `sort_direction`, and `disposal` values and omit `lot_id`.

### 2.8 Consider payload size for all-lot dropdowns

**Files:**

- `app/Repositories/PhaseRepository.php`
- `app/Services/BurialRecordService.php`

`getCreateData()` and `getShowData()` now return every lot in every phase so occupied lots remain selectable. This is correct for shared lots, but the payload can grow significantly.

Measure create/edit response size on production-sized data. If it is slow:

- Load lots only for the selected phase/cluster.
- Add a searchable lot endpoint instead of sending all lots up front.
- Keep the count metadata as an aggregate query rather than loading relations.

---

## 3. Tests to Add or Update

### 3.1 Fix the full-suite blocker

**File:** `tests/Feature/Admin/DashboardTest.php`

The `DashboardService` mock does not expect `getBurialCountForPeriod()`, but `DashboardController::index()` calls it for the summary tab. This failure predates the shared-lot work, but it prevents a clean full-suite run.

Add an expectation such as:

```php
$mock->shouldReceive('getBurialCountForPeriod')
    ->andReturn(0);
```

### 3.2 Add coverage for the new lot filter and redirect

Add feature tests for:

- Admin lot “View Details” redirects to `admin.burial_records.index` with `lot_id`.
- Clerk lot “View Details” redirects to `clerk.burial_records.index` with `lot_id`.
- The index returns only records for the requested lot.
- `selected_lot` contains the lot, cluster, and phase labels.
- Changing sort/filter preserves `lot_id`.
- An invalid `lot_id` fails validation.

### 3.3 Add API and reporting coverage

- `Api\LotManagementController::getLots()` returns `burial_count` for active records only and ignores archived records.
- `Api\LotManagementController::getClusters()` excludes archived records from `occupied_lots`.
- `PhaseResource` no longer counts archived records after the fix above.
- Phase availability report counts distinct occupied lots, never produces a negative `available_lots`, and handles a lot with multiple records correctly.

### 3.4 Run the complete suite

After targeted tests pass:

```bash
php artisan test --compact
vendor/bin/pint --dirty --format agent
npm run build
```

---

## 4. Documentation to Update

These files still describe the old “one active record per lot” behavior and should either be updated or annotated as historical.

| File | Why it is stale | Suggested action |
|---|---|---|
| `docs/todo/done/burial-record-archiving-plan.md` | Says restore is blocked or clears the lot when another active record occupies it. Restore now always keeps the lot. | Add a “Superseded by shared-lot refactor” note near the recovery section. |
| `docs/todo/done/panteon-seeder-memory-fix.md` | Documents the `usedLotIds` seeder behavior that was removed. | Add a note that shared-lot seeding intentionally no longer skips used lots. |
| `docs/System-Overview.md` | Describes `BurialRecord` as linking a deceased person to a lot, without the many-to-one nuance. | Add one sentence: multiple active burial records may share a lot; occupancy is informational. |
| `docs/todo/shared-lot-relationship-plan.md` | Rollout checklist is still unchecked. | Mark items complete as each environment is verified. |

Do not rewrite historical done documents as if the old behavior never existed; a short superseded note is enough.

---

## 5. Known Behaviors and Decisions

These are intentional and should not be “fixed” without a product decision:

- Two active burial records may share a lot.
- Occupancy does not block assignment; it is informational only.
- Archived records keep their `lot_id`.
- Restore never clears `lot_id` automatically.
- Deleting a lot still sets its burial records’ `lot_id` to `NULL` via `nullOnDelete`.
- Existing unassigned records are handled manually, not by an importer backfill.
- Concurrent clerks can assign the same lot by design. If a warning is desired later, make it a soft confirmation, not a hard block.

---

## 6. Suggested Order of Work

1. Verify production/staging schema and audit unassigned records.
2. Fix `DashboardTest` and run the full suite.
3. Exclude archived records from lot-management map APIs.
4. Align map/report occupancy labels and values.
5. Add filter/redirect/API/report tests.
6. Run fresh-seed and import smoke tests.
7. Update stale documentation.
8. Optional UX work: edit-dropdown counts, visitor-map multi-record selection, payload optimization.
