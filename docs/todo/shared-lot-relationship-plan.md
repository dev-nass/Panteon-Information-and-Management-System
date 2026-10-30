# Shared Lot Relationship Plan

**Status:** Draft  
**Area:** Burial records, lots, imports, seeding  
**Goal:** Allow many deceased/burial records to reference the same lot, while keeping the lot assignment persisted on `burial_records.lot_id`.

---

## 1. Current State

The database already supports the desired many-to-one relationship:

- `burial_records.lot_id` is a nullable foreign key to `lots.id`.
- The foreign key index is **not unique**.
- `BurialRecord::lot()` is a `belongsTo` relationship.
- `Lot::burialRecords()` is a `hasMany` relationship.
- `deceased_records` does **not** have a `lot_id` column.

The current “one lot = one burial record” behavior is enforced by application logic, not by the schema:

- The clerk create form filters out lots where `is_occupied === true`.
- The clerk edit form only receives “available” lots from `PhaseRepository`.
- Restoring an archived burial record nulls its `lot_id` if another active record uses the same lot.
- The admin importer preloads occupied lot IDs into `$usedLotIds` and skips them.
- `PanteonDataSeeder` tracks `$usedLotIds` in memory and skips repeat lot assignments.

### Relevant files

| File | Current behavior |
|---|---|
| `database/migrations/2026_03_01_075233_create_burial_records_table.php` | Defines `burial_records.lot_id` as nullable FK with `nullOnDelete` |
| `app/Models/BurialRecord.php` | `lot()` belongsTo |
| `app/Models/Lot.php` | `burialRecords()` hasMany |
| `app/Repositories/PhaseRepository.php` | Returns only lots without active burial records for editing |
| `app/Services/BurialRecordService.php` | Builds create data, restores records, clears lot on conflict |
| `app/Http/Controllers/Admin/ImportingController.php` | Skips occupied lots during import |
| `app/Services/RecordNormalizationService.php` | Accepts `$usedLotIds` and rejects used lots |
| `database/seeders/PanteonDataSeeder.php` | Tracks `$usedLotIds` during seeding |
| `resources/js/Pages/Shared/BurialRecords/CreateView.vue` | Filters occupied lots out of the dropdown |
| `resources/js/Pages/Shared/BurialRecords/ShowView.vue` | Filters occupied lots out of edit and clears lot on restore |

---

## 2. Target Behavior

- Many **active** burial records may reference the same lot.
- The lot assignment is always stored on `burial_records.lot_id`.
- `deceased_records` remains independent of lots.
- “Occupied” becomes an informational label:
  - A lot is occupied when it has at least one active burial record.
  - Being occupied does not prevent another record from being assigned to it.
- Archived records keep their lot.
- Restoring an archived record keeps its original lot.
- A burial record may still be unassigned when the source lot cannot be found.

### Assumption to confirm

This plan assumes multiple **active** burial records may share a lot. If the intended rule is “only one active record per lot, but historical/archived records may share it,” the restore and availability changes should be adjusted.

---

## 3. Schema Decision

**No new migration is expected in this repository.**

Before deployment, verify each environment:

```sql
SHOW COLUMNS FROM burial_records;
SHOW INDEX FROM burial_records WHERE Column_name = 'lot_id';
SHOW COLUMNS FROM deceased_records;
```

Expected:

- `burial_records.lot_id` exists.
- Its index is non-unique.
- `deceased_records.lot_id` does not exist.

If production differs from the repository migrations:

1. Backfill `burial_records.lot_id` from any legacy `deceased_records.lot_id`.
2. Drop the legacy column.
3. Drop any unique constraint on `burial_records.lot_id`.

Only add a migration if one of these conditions is actually found.

---

## 4. Implementation Plan

### Phase 1 — Domain and repository cleanup

**Files:**

- `app/Repositories/LotRepository.php`
- `app/Services/BurialRecordService.php`
- `app/Models/DeceasedRecord.php` (optional)

**Changes:**

- Rename or repurpose `LotRepository::validateLotAvailability()` to something like `findLotForAssignment()`. It currently only checks existence; its name implies occupancy validation that no longer applies.
- Update `BurialRecordService::store()` to use the renamed method.
- Optionally add an explicit read-only relationship on `DeceasedRecord`:

```php
public function lot(): HasOneThrough
{
    return $this->hasOneThrough(
        Lot::class,
        BurialRecord::class,
        'deceased_record_id',
        'id',
        'id',
        'lot_id',
    );
}
```

Do not add a `lot_id` column to `deceased_records`. Writes must continue to go to `burial_records.lot_id`.

---

### Phase 2 — Manual clerk encoding

**Files:**

- `app/Http/Requests/Clerk/BurialRecordStoreRequest.php`
- `app/Services/BurialRecordService.php`
- `app/Repositories/PhaseRepository.php`
- `resources/js/Pages/Shared/BurialRecords/CreateView.vue`
- `resources/js/Pages/Shared/BurialRecords/ShowView.vue`

**Changes:**

1. Add `exists:lots,id` to the `lot_id` rule in `BurialRecordStoreRequest`.

2. `BurialRecordService::getCreateData()`:
   - Return all lots for the selected phase/cluster.
   - Keep `is_occupied` or add `burial_count` as display-only metadata.
   - Do not remove occupied lots from the result.

3. `PhaseRepository::getPhasesWithAvailableLotsForCreate()`:
   - Rename to `getPhasesWithLotsForCreate()`.
   - Return every lot.
   - Optionally replace the `clusters.lots.burialRecords` eager load with an active-record count to reduce payload size.

4. `PhaseRepository::getPhasesWithAvailableLotsForShow()`:
   - Rename to `getPhasesWithLotsForShow()`.
   - Remove the `whereDoesntHave('burialRecords')` / `orWhereHas(...)` filter.
   - Return every lot so the edit dropdown can select any lot.

5. `BurialRecordService::restore()`:
   - Remove the `$shouldNullLot` check.
   - Remove the code that sets `lot_id = null` when another active record uses the lot.
   - Restoring always keeps the original `lot_id`.

6. `CreateView.vue`:
   - Remove:

```js
cluster?.lots?.filter((lot) => !lot.is_occupied) || []
```

   - Use all lots from the selected cluster.
   - Optionally show `(N records)` next to occupied lots without disabling them.

7. `ShowView.vue`:
   - Remove the `!lot.is_occupied || lot.id == selectedLotId` filter.
   - Remove the restore warning that says the record will be restored as “Unassigned.”
   - Remove the “lot may be occupied” fallback in the restore error handler.

---

### Phase 3 — Admin importing

**Files:**

- `app/Http/Controllers/Admin/ImportingController.php`
- `app/Services/RecordNormalizationService.php`

**Changes:**

1. Remove the occupied-lot preload:

```php
$usedLotIds = Lot::whereHas('burialRecords')->pluck('id')->flip()->toArray();
```

2. Remove per-row tracking:

```php
$usedLotIds[$lot->id] = true;
```

3. Remove `$usedLotIds` from all cleanup/unset calls.

4. Change `RecordNormalizationService::findLotByClusterAndApt()`:

```php
public function findLotByClusterAndApt(
    array $lotsMap,
    array $lotsMapGeneric,
    string $phaseKey,
    string $clusterName,
    ?string $typeHint,
    string $rowLetter,
    string $column,
): ?Lot
```

Remove every `isset($usedLotIds[$candidate->id])` check. The lookup should only resolve phase, cluster, type, row, and column.

5. Update both import calls and the seeder calls to the new signature.

6. Change the import warning from:

> Lot ... was not found or is already occupied — burial saved without a lot.

to:

> Lot ... was not found — burial saved without a lot.

7. Keep creating the burial record with:

```php
BurialRecord::create([
    'deceased_record_id' => $deceased->id,
    'lot_id' => $lot?->id,
    'user_id' => auth()->id(),
]);
```

No change to the persistence target.

#### Existing unassigned imports

Records previously imported with `lot_id = null` because their lot was occupied cannot be fixed automatically from the database alone. The importer did not store the original phase/cluster/apt label on the record.

Choose one of these approaches:

- **Recommended:** Add a backfill path to the importer. When a duplicate deceased record already exists and its active burial record has `lot_id = null`, resolve the lot from the file and update the existing burial record instead of skipping it.
- **Alternative:** Leave those records unassigned and let clerks reassign them through the now-unrestricted edit screen.

---

### Phase 4 — Seeding

**File:**

- `database/seeders/PanteonDataSeeder.php`

**Changes:**

1. Remove:

```php
$usedLotIds = [];
```

2. Remove:

```php
$usedLotIds[$lot->id] = true;
```

3. Remove `$usedLotIds` from cleanup/unset calls.

4. Update comments that describe lots as single-use.

After this change, repeated phase/cluster/apt combinations in the source spreadsheet will all map to the same lot instead of the second and later rows becoming unassigned.

#### Fresh seed verification

Run in local/development only:

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

Also verify that records which previously would have been unassigned now have a lot:

```sql
SELECT COUNT(*) AS unassigned
FROM burial_records
WHERE lot_id IS NULL;
```

Do not blindly rerun the seeder on an existing production database. Existing duplicate detection will skip already-seeded deceased records, and the seeder is not a migration tool.

---

### Phase 5 — Reporting and lot-detail consistency

These changes are not strictly required for persistence, but they prevent misleading values once lots can have multiple records.

**Files:**

- `app/Http/Controllers/Admin/GenerateReportController.php`
- `app/Http/Controllers/Api/LotManagementController.php`
- `app/Http/Controllers/Admin/LotManagementController.php`
- `app/Http/Controllers/Clerk/LotManagementController.php`
- `app/Http/Requests/BurialRecordIndexRequest.php`
- `app/Repositories/BurialRecordRepository.php`

**Changes:**

1. `GenerateReportController::getPhaseAvailabilityData()`:
   - `available_lots = total_lots - totalOccupants` breaks when a lot has multiple records and can become negative.
   - Count **distinct lots with active burial records**, not burial records, for availability and occupancy rate.

2. `Api\LotManagementController::getLots()`:
   - Count only active burial records when determining `status`.
   - Optionally include `burial_count`.

3. `Api\LotManagementController::getClusters()`:
   - Ensure `occupied_lots` excludes archived records, matching dashboard/report semantics.

4. Lot detail navigation:
   - `Admin\LotManagementController::show()` and `Clerk\LotManagementController::show()` currently redirect to `$lot->burialRecords()->first()`.
   - With many records per lot, that selection is arbitrary.
   - Recommended: add a `lot_id` filter to the burial-record index and redirect there so all records for the lot are listed.
   - If the filter is out of scope, at least document that the link opens only one record.

5. Map modals already support multiple records per lot:
   - `LotResource` returns `burial_count`.
   - Map endpoints return a `burial_records` collection.
   - Verify no “first record only” assumptions remain.

---

## 5. Tests

Add a focused feature test, for example:

`tests/Feature/Clerk/BurialRecordSharedLotTest.php`

### Test cases

1. **Manual store shares a lot**
   - Call `BurialRecordService::store()` twice with different deceased/applicant data and the same `lot_id`.
   - Assert both burial records exist and both reference the same lot.

2. **Create data includes occupied lots**
   - Create one burial record for a lot.
   - Assert `BurialRecordService::getCreateData()` still returns that lot.

3. **Restore keeps the lot**
   - Create two active records on the same lot.
   - Archive one.
   - Restore it.
   - Assert its `lot_id` is unchanged.

4. **Normalizer returns the same lot repeatedly**
   - Resolve the same phase/cluster/row/column twice.
   - Assert both lookups return the same `Lot`.

5. **Import assigns duplicate lots**
   - Upload a small CSV with two different deceased rows pointing to the same phase/cluster/apt.
   - Assert two burial records are created with the same `lot_id`.
   - Assert no “already occupied” warning is produced.

### Commands

```bash
php artisan test --compact --filter=BurialRecordSharedLot
vendor/bin/pint --dirty --format agent
npm run build
```

Seeder behavior depends on the external Excel file, so verify it manually with the SQL audit above rather than adding a full-seeder test.

---

## 6. Data and Rollout Checklist

- [ ] Confirm in staging/production that `burial_records.lot_id` exists and is non-unique.
- [ ] Confirm `deceased_records.lot_id` does not exist.
- [ ] Audit existing burial records with `lot_id IS NULL`.
- [ ] Decide whether to re-import/backfill existing unassigned records or reassign manually.
- [ ] Apply code changes for manual encoding, importing, and seeding.
- [ ] Run targeted tests and the full suite.
- [ ] Run `vendor/bin/pint --dirty --format agent`.
- [ ] Run `npm run build`.
- [ ] Verify a fresh local seed produces shared lot assignments.
- [ ] Verify an admin import file with repeated lot numbers assigns the same lot to multiple records.
- [ ] Verify clerk create/edit can select an occupied lot.
- [ ] Verify restoring an archived record keeps its lot.
- [ ] Verify lot availability reports do not produce negative available-lot counts.

---

## 7. Risks and Edge Cases

- **Ambiguous lot lookup:** Removing `$usedLotIds` means import rows without a cluster-type hint will always resolve to the first matching lot type instead of falling through to another unused type. The typed lookup still handles explicit `UG`/type hints.
- **Reports:** Any calculation that subtracts burial-record count from lot count can become inaccurate or negative. Use distinct occupied lots instead.
- **Existing data:** Pre-change imports may have left records unassigned. These records need a backfill or manual cleanup.
- **Archiving:** Archived records remain attached to the lot for history. Restoring no longer clears the lot.
- **Deleting lots:** `lot_id` is nullable with `nullOnDelete`, so deleting a lot still leaves its burial records unassigned.
- **Permissions:** No authorization changes are needed, but ensure admins and clerks see consistent lot-selection behavior.

---

## 8. Open Questions

1. Should multiple **active** burial records share a lot, or only one active record with archived history sharing the lot?
2. Should existing records with `lot_id = null` be backfilled through a re-import path, or handled manually?
3. Should the lot “View Details” flow list all burial records for a lot, or is redirecting to the first record acceptable for now?
