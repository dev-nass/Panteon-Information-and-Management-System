# Burial Record Archiving — Implementation Plan

## Overview

Replace hard deletion of `burial_records` with an archiving workflow. Archived records are hidden from the default Burial Records table and only appear when a specific `Archived` filter is applied. On `ShowView`, an archived record shows a single green-accented `Recover` button instead of the normal action set (`Edit`, `Delete`, `View on Map`, `COS`). Uses new columns on `burial_records` — no separate archive table.

---

## Current State

| Area | Status |
|------|--------|
| `app/Models/BurialRecord.php:10` | `HasFactory` only, `$fillable = ['deceased_record_id','lot_id','user_id']`, no `archived_*` cols, no scopes |
| `database/migrations/2026_03_01_075233_create_burial_records_table.php:1` | No `archived_at` / soft-delete columns; all 16 migrations have zero `softDeletes()` usage |
| `app/Http/Controllers/Clerk/BurialRecordController.php:115` `destroy()` | Hard `logActivity('deleted')` + `$burial_record->delete()` → `302` to index; only delete route in app |
| `app/Repositories/BurialRecordRepository.php:15` `getBurialRecordsWithFilters()` | Filters: `buried|pending|assigned|unassigned` + `disposal`; no archived handling; defaults to returning all non-filtered rows |
| `app/Http/Requests/BurialRecordIndexRequest.php:24` | `filter` nullable string, defaults to `'all'` via `filterValue()`; no validation of `archived` |
| `app/Services/BurialRecordService.php:23` `index()` | `->paginate(25)` passthrough, no archived awareness; `getShowData()` loads single record without archived guard |
| `resources/js/Pages/Shared/BurialRecords/IndexView.vue:90` | Props `burial_records`, `filters`; search via `useSearchBurialRecords`; `applyFilter()` + `applyDisposalFilter()` with `preserveState:true`; table shows `ID|Full Name|Birth|Death|Burial|Lot Status` |
| `resources/js/Pages/Shared/BurialRecords/ShowView.vue:121` `deleteBurialRecord()` | `confirm("...cannot be undone")` + `router.delete(route('clerk.burial_records.destroy'))`; header actions: `View on Map`, `Edit`, `Delete` (red), `COS` (all `v-if="userRole === 'clerk'" && !editing`) |
| `app/Http/Controllers/Api/MapDataController.php`, `GenerateReportController.php`, `BurialScheduleController.php` | Query `BurialRecord::with(...)` without archived exclusion |
| `docs/todo/todo.md:17` | Open TODO: `Archive Burial Records, no complete deletion — include reason "pull out","transfer"...` |
| `ActivityLog` enum | `action: ['created','updated','deleted','role_changed','imported','generated']` — no `archived`/`restored` |

**Gap:** Every delete is permanent; `deceased_records`/`applicants` remain orphaned after burial delete. No reason tracking, no restore path, no dedicated archived UX.

---

## Target State

### 1. Database — Add Columns to `burial_records` (Not a New Table)

Single migration adds archiving state to the existing pivot:

```php
$table->timestamp('archived_at')->nullable()->after('updated_at')->index();
$table->enum('archived_reason', ['pull_out','transfer','expired','other'])->nullable()->after('archived_at');
$table->foreignIdFor(User::class, 'archived_by')->nullable()->constrained()->nullOnDelete()->after('archived_reason');
$table->text('archived_notes')->nullable()->after('archived_by');
```

Lot semantics: `lot_id` is **kept** on archived row for audit/history, but archived rows are excluded from occupancy counts — the lot immediately becomes available. On recover, lot availability is re-validated.

`activity_logs.action` enum extended via second migration: add `'archived','restored'` (or alter to `string` if enum ALTER is painful on MySQL).

### 2. Hidden-by-Default + `Archived` Filter on Index

- Default behavior (`filter=all|buried|pending|assigned|unassigned` or no filter): `WHERE burial_records.archived_at IS NULL`. Archived records never appear.
- Only when `filter=archived`: `WHERE burial_records.archived_at IS NOT NULL`. All other filters ignore archived rows.
- Index filter dropdown gains a new radio `Archived` next to `All|Buried|Pending|Assigned|Unassigned`. Badge label shows `Archived` when active. No separate page — same `IndexView.vue` with filter param.
- Search + disposal filters compose with archived filter (e.g., `filter=archived&disposal=cremation&search=Juan`).

### 3. ShowView — Single Green `Recover` Button for Archived Records

- Normal (active) record: header actions unchanged — `View on Map`, `Edit` (green tint), `Delete` → repurposed to **Archive** (red, opens archive reason modal), `COS`. Tabs editable.
- Archived record: **all normal actions hidden**. Single button visible:

```vue
<button
    v-if="isArchived"
    @click="recoverBurialRecord"
    class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200"
>
    <!-- rotate-ccw icon -->
    Recover
</button>
```

- Also show an amber banner at top of card when archived: `Archived on {archived_at} — Reason: {archived_reason} — By {archived_by}` + `archived_notes` if present.
- Archived record tabs are read-only (`editing` forced `false`, Edit button not rendered).

### 4. Archive Flow (Replaces Delete)

- Clerk clicks `Archive` (currently `Delete`) → opens `HSOverlay` modal (not native `confirm`):
  - `select archived_reason` required: `pull_out | transfer | expired | other`
  - `textarea archived_notes` required only when `other`, optional otherwise
  - `Cancel` / `Archive` (red) buttons
- `Archive` posts to `POST /clerk/burial-records/{burial_record}/archive` → sets `archived_at=now()`, `archived_reason`, `archived_by=auth()->id()`, `archived_notes`; logs `archived`; frees lot.
- Toast: `Burial record archived successfully.` + redirect to `index` (or stay with archived banner).

### 5. Recover Flow

- Archived `ShowView` green `Recover` click → `confirm("Recover this burial record?")` → `POST /clerk/burial-records/{burial_record}/restore`:
  - Guard: `abort(422)` if record is not archived.
  - Guard: if `lot_id` lot is now occupied by another **active** burial → `422` with message `Lot is now occupied. Please select a new lot after recovery or free the lot first.` — recovery nulls lot? Decision: keep `lot_id` but allow recovery to succeed and mark lot as contested; simpler is to block recovery until lot free, and surface lot picker on recover modal. **Plan: block + require lot reassignment.**
  - On success: nulls `archived_*` cols, logs `restored`, toast `Burial record recovered successfully.`
- After recover, header actions revert to normal set.

---

## Backend Changes

### 1. Migration

File: `database/migrations/2026_xx_xx_add_archiving_to_burial_records_table.php`

```php
Schema::table('burial_records', function (Blueprint $table) {
    $table->timestamp('archived_at')->nullable()->after('updated_at')->index();
    $table->enum('archived_reason', ['pull_out','transfer','expired','other'])->nullable()->after('archived_at');
    $table->foreignIdFor(User::class, 'archived_by')->nullable()->constrained()->nullOnDelete()->after('archived_reason');
    $table->text('archived_notes')->nullable()->after('archived_by');
});
```

File: `database/migrations/2026_xx_xx_extend_activity_logs_action_enum.php` — add `archived`, `restored` to `activity_logs.action` enum (`DB::statement("ALTER TABLE activity_logs MODIFY action ENUM(...)")`).

### 2. Model — `app/Models/BurialRecord.php:10`

```php
protected $fillable = ['deceased_record_id','lot_id','user_id','archived_at','archived_reason','archived_by','archived_notes'];

protected $casts = ['archived_at' => 'datetime'];

public function archivedBy(): BelongsTo { return $this->belongsTo(User::class, 'archived_by'); }

public function isArchived(): bool { return $this->archived_at !== null; }

public function scopeActive($q) { return $q->whereNull('archived_at'); }
public function scopeArchived($q) { return $q->whereNotNull('archived_at'); }
```

No global scope — filtering stays explicit to avoid surprising `MapDataController` spatial queries.

### 3. Repository — `app/Repositories/BurialRecordRepository.php:15`

```php
public function getBurialRecordsWithFilters(...): Builder
{
    return $this->query()->with(['deceasedRecord','lot','user'])
        // archived handling — must be first
        ->when($filter === 'archived', fn($q) => $q->whereNotNull('burial_records.archived_at'),
               fn($q) => $q->whereNull('burial_records.archived_at'))
        // existing filters below now implicitly active-only
        ->leftJoin(...)
        ->when($filter === 'buried', fn($q) => $q->whereNotNull('deceased_records.date_of_depository'))
        // ... assigned/unassigned/disposal/search/orderBy unchanged
}
```

### 4. Request — `app/Http/Requests/BurialRecordIndexRequest.php:24`

Allow `archived` in validation (or keep `nullable|string` and normalize):

```php
'filter' => ['nullable','string','in:all,buried,pending,assigned,unassigned,archived'],
```

Add archived-specific request:

`app/Http/Requests/Clerk/BurialRecordArchiveRequest.php`:

```php
public function rules(): array {
    return [
        'archived_reason' => ['required','in:pull_out,transfer,expired,other'],
        'archived_notes' => ['nullable','string','max:1000','required_if:archived_reason,other'],
    ];
}
```

### 5. Service — `app/Services/BurialRecordService.php`

Add to `BurialRecordService`:

```php
public function archive(BurialRecord $record, array $data, int $by): BurialRecord
{
    abort_if($record->archived_at, 422, 'Already archived.');
    return DB::transaction(fn() => tap($record, fn() => $record->update([
        'archived_at' => now(), 'archived_reason' => $data['archived_reason'],
        'archived_by' => $by, 'archived_notes' => $data['archived_notes'] ?? null,
    ])));
}

public function restore(BurialRecord $record): BurialRecord
{
    abort_if(! $record->archived_at, 422, 'Not archived.');
    if ($record->lot_id && BurialRecord::active()->where('lot_id', $record->lot_id)->exists()) {
        throw ValidationException::withMessages(['lot_id' => 'Lot is now occupied. Reassign after recovery.']);
    }
    return DB::transaction(fn() => tap($record, fn() => $record->update([
        'archived_at' => null, 'archived_reason' => null, 'archived_by' => null, 'archived_notes' => null,
    ])));
}
```

Update `getPhasesWithAvailableLotsForCreate/Show` consumers — occupancy check becomes `lot->burialRecords()->active()->exists()` (see PhaseRepository update).

### 6. Controller — `app/Http/Controllers/Clerk/BurialRecordController.php`

```php
public function archive(BurialRecordArchiveRequest $request, BurialRecord $burial_record) {
    $this->service->archive($burial_record, $request->validated(), auth()->id());
    $this->logActivity('archived', $burial_record, "Archived burial record for {$burial_record->deceasedRecord->first_name} {$burial_record->deceasedRecord->last_name}", null, $request->validated());
    return to_route('clerk.burial_records.index')->with('success','Burial record archived successfully.');
}

public function restore(BurialRecord $burial_record) {
    $this->service->restore($burial_record);
    $this->logActivity('restored', $burial_record, "Restored burial record for {$burial_record->deceasedRecord->first_name} {$burial_record->deceasedRecord->last_name}");
    return back()->with('success','Burial record recovered successfully.');
}

// Keep destroy() for 1 release as alias to archive() OR deprecate with 410 and remove route in next version.
```

`show()` must allow loading archived records (no `active()` scope on find — route model binding already does).

### 7. Resources

`app/Http/Resources/BurialRecordResource.php:18` — add:

```php
'archived' => $this->when($this->archived_at, fn() => [
    'at' => $this->archived_at?->toISOString(),
    'reason' => $this->archived_reason,
    'notes' => $this->archived_notes,
    'by' => $this->whenLoaded('archivedBy', fn() => new UserResource($this->archivedBy)),
]),
'is_archived' => (bool) $this->archived_at,
```

Ensure `Clerk/BurialRecordController:show()` eager loads `archivedBy`.

### 8. Routes — `routes/clerk.php:31`

```php
Route::controller(BurialRecordController::class)->group(function () {
    Route::get('/burial-records', 'index')->name('burial_records.index');
    Route::get('/burial-records/create', 'create')->name('burial_records.create');
    Route::post('/burial-records', 'store')->name('burial_records.store');
    Route::get('/burial-records/{burial_record}', 'show')->name('burial_records.show');
    Route::post('/burial-records/{burial_record}', 'update')->name('burial_records.update');
    Route::post('/burial-records/{burial_record}/archive', 'archive')->name('burial_records.archive');
    Route::post('/burial-records/{burial_record}/restore', 'restore')->name('burial_records.restore');
    // DELETE destroy deprecated — keep one release or remove: Route::delete('/burial-records/{burial_record}', 'destroy')->name('burial_records.destroy');
});
```

Same `archive`/`restore` read behavior for `routes/admin.php` (admin can view archived but only clerk can archive/restore, unless policy says otherwise — enforce via `clerk` middleware or `abort_if(auth()->user()->role !== 'clerk')` inside controller).

### 9. Other Consumers — Exclude Archived Everywhere

| File | Change |
|------|--------|
| `app/Repositories/PhaseRepository.php:57` `getPhasesWithAvailableLotsForShow/ForCreate` | `lots.burialRecords` check → `->active()` |
| `app/Http/Controllers/Api/MapDataController.php` | `burialRecords()` / `partialBurialRecords()` / `clusterBurialRecords()` → `->active()` |
| `app/Http/Controllers/Api/MapSearchDataController.php` | `search()` → exclude archived |
| `app/Http/Controllers/Clerk/BurialScheduleController.php` | calendar events → exclude archived |
| `app/Http/Controllers/Admin/GenerateReportController.php` | reports default exclude archived; add optional `include_archived` toggle if needed |
| `app/Http/Controllers/Clerk/DashboardController.php` | stats exclude archived |

---

## Frontend Changes

### `resources/js/Pages/Shared/BurialRecords/IndexView.vue:90`

**Filter dropdown** — add `Archived` radio after `Unassigned`:

```vue
<label for="filter-archived" class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800">
    <input type="radio" name="filter" value="archived" id="filter-archived"
        :checked="filters.filter === 'archived'" @change="applyFilter('archived')" />
    <span class="ms-3 text-sm text-gray-800 dark:text-neutral-200">Archived</span>
</label>
```

**Badge label** — extend ternary:

```js
filters.filter === 'archived' ? 'Archived' : filters.filter === 'buried' ? 'Buried' : ...
```

**Table row** — when `record.is_archived` show amber `Archived` pill instead of `Assigned/Unassigned`; optionally mute row `opacity-60`.

**Empty state** — `No archived records found` when `filter==='archived'`.

No extra page — same `IndexView` handles archived via filter param (preserves pagination, search, disposal).

### `resources/js/Pages/Shared/BurialRecords/ShowView.vue:121`

**State:**

```js
const isArchived = computed(() => !!props.burial_record.data.is_archived);
```

**Header actions block** `resources/js/Pages/Shared/BurialRecords/ShowView.vue:407`:

```vue
<!-- Archived: single green Recover -->
<template v-if="isArchived">
    <button @click="recoverBurialRecord"
        class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        Recover
    </button>
</template>

<!-- Active: existing actions -->
<template v-else>
    <button v-if="userRole==='clerk'" @click="editing=!editing" class="...">Edit</button>
    <button v-if="userRole==='clerk'" @click="openArchiveModal" class="... bg-red-500/10 text-red-500 ...">Archive</button>
    <!-- View on Map, COS unchanged -->
</template>
```

Guard `editing` — `watch(isArchived, v => { if(v) editing.value=false })`.

**Archive modal** — new `HSOverlay` `#archive-modal` (mirror `#hs-cookies`):

```vue
<select v-model="archiveForm.archived_reason" required>
    <option value="">Select reason</option>
    <option value="pull_out">Pull Out</option>
    <option value="transfer">Transfer</option>
    <option value="expired">Expired</option>
    <option value="other">Other</option>
</select>
<textarea v-model="archiveForm.archived_notes" :required="archiveForm.archived_reason==='other'" placeholder="Details (required if Other)" />
```

```js
const archiveForm = ref({ archived_reason: '', archived_notes: '' });
const openArchiveModal = () => HSOverlay.open('#archive-modal');
const archiveBurialRecord = () => {
    router.post(route('clerk.burial_records.archive', props.burial_record.data.burial.id),
        archiveForm.value, {
            onSuccess: () => { $toast.success('Burial record archived successfully!'); HSOverlay.close('#archive-modal'); },
            onError: () => $toast.error('Failed to archive burial record.'),
        });
};
const recoverBurialRecord = () => {
    if (confirm('Recover this burial record? The lot will be re-occupied if still available.')) {
        router.post(route('clerk.burial_records.restore', props.burial_record.data.burial.id), {}, {
            onSuccess: () => $toast.success('Burial record recovered successfully!'),
            onError: (e) => $toast.error(e.lot_id || 'Failed to recover — lot may be occupied.'),
        });
    }
};
```

**Archived banner** — above card container, only when `isArchived`:

```vue
<div v-if="isArchived" class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 dark:border-amber-800 px-4 py-3">
    <span class="text-amber-600 dark:text-amber-400">Archived on {{ burial_record.data.archived.at }} — Reason: {{ burial_record.data.archived.reason }}</span>
    <span v-if="burial_record.data.archived.notes" class="text-sm text-gray-600 dark:text-neutral-300">{{ burial_record.data.archived.notes }}</span>
</div>
```

**Removed:** `deleteBurialRecord()` (`ShowView.vue:121`) replaced by archive modal flow.

### `resources/js/composables/burial_records/useSearchBurialRecords.js`

Ensure `filter` param is preserved in debounced `router.get` — already passes `filter`, no change.

---

## Files to Modify

| File | Change |
|------|--------|
| `database/migrations/2026_xx_xx_add_archiving_to_burial_records_table.php` | **New** — `archived_at` (timestamp, indexed), `archived_reason` (enum), `archived_by` (FK), `archived_notes` |
| `database/migrations/2026_xx_xx_extend_activity_logs_action_enum.php` | **New** — add `archived`, `restored` |
| `app/Models/BurialRecord.php:10` | Add `archived_*` to `$fillable`, `$casts`, `archivedBy()` relation, `isArchived()`, `scopeActive/Archived` |
| `app/Repositories/BurialRecordRepository.php:15` | `archived` filter branch; default `whereNull(archived_at)` |
| `app/Repositories/PhaseRepository.php` | Occupancy checks → `->active()` |
| `app/Http/Requests/BurialRecordIndexRequest.php:24` | Allow `archived` in `filter` enum |
| `app/Http/Requests/Clerk/BurialRecordArchiveRequest.php` | **New** — validate `archived_reason`/`archived_notes` |
| `app/Services/BurialRecordService.php` | Add `archive()` + `restore()` with lot-occupied guard |
| `app/Http/Controllers/Clerk/BurialRecordController.php:115` | Add `archive()`/`restore()`; deprecate/remove `destroy()` |
| `app/Http/Controllers/Admin/BurialRecordController.php` | Same `show` eager load `archivedBy`; index passes archived filter through |
| `app/Http/Resources/BurialRecordResource.php:18` | Expose `is_archived` + `archived{at,reason,notes,by}` |
| `routes/clerk.php:31` | + `POST /archive`, `POST /restore`; deprecate `DELETE` |
| `routes/admin.php` | Ensure archived filter reachable for admin index/show |
| `app/Http/Controllers/Api/MapDataController.php` | Exclude archived from all burial queries |
| `app/Http/Controllers/Api/MapSearchDataController.php` | Exclude archived |
| `app/Http/Controllers/Clerk/BurialScheduleController.php` | Exclude archived from calendar |
| `app/Http/Controllers/Admin/GenerateReportController.php` | Exclude archived by default |
| `resources/js/Pages/Shared/BurialRecords/IndexView.vue:90` | Add `Archived` radio + badge + row pill + empty state |
| `resources/js/Pages/Shared/BurialRecords/ShowView.vue:121` | Replace delete with archive modal; add green `Recover` single-button state + archived banner + `isArchived` guards |
| `resources/js/Pages/Shared/BurialRecords/ShowView.vue:1` (imports) | Import `useToast` reuse; no new deps |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `app/Models/DeceasedRecord.php`, `Applicant.php`, `Lot.php` | No schema change; burial archiving keeps deceased/applicant intact (audit) |
| `app/Repositories/DeceasedRecordRepository.php` | No logic change |
| `resources/js/Pages/Shared/BurialRecords/CreateView.vue` | Creation unaffected |
| `database/factories/BurialRecordFactory.php` | Factory update optional (add `archived` state) but not required for plan |

---

## Implementation Order

1. Migration: `burial_records` archived cols + index + FK
2. Migration: `activity_logs.action` enum extension
3. Model: `BurialRecord` fillable/casts/relations/scopes/helpers
4. Request: `BurialRecordArchiveRequest` + update `BurialRecordIndexRequest` to allow `archived`
5. Repository: `BurialRecordRepository` archived filtering + `PhaseRepository` active occupancy
6. Service: `archive()` / `restore()` with lot-occupied guard + transaction
7. Controller: `archive()` / `restore()` + `logActivity` + deprecate `destroy()`
8. Resource: expose `is_archived` / `archived` to Inertia
9. Routes: `clerk.php` archive/restore (admin show/index passthrough)
10. API/Report/Schedule controllers: add `->active()` exclusion
11. Frontend `IndexView.vue`: `Archived` filter radio + badge + row state
12. Frontend `ShowView.vue`: archive modal + green `Recover` single-button + banner + guards
13. Verify: `vendor/bin/pint --dirty`, `npx prettier --write resources/js/Pages/Shared/BurialRecords/`, `npm run build`, manual filter + archive + recover + lot-occupied edge case

---

## Verification Checklist

- [ ] `GET /clerk/burial-records` (no filter) hides archived records
- [ ] `GET /clerk/burial-records?filter=archived` shows **only** archived records; `all|buried|pending|assigned|unassigned` still hide archived
- [ ] Search + disposal compose with `filter=archived` (`?filter=archived&disposal=cremation&search=Juan`)
- [ ] Active `ShowView` shows `Edit | Archive (red) | View on Map | COS`; no `Recover`
- [ ] Archived `ShowView` shows **only** green `Recover` (with `bg-green-500/10` accent, `hover:bg-green-500/20`) + amber archived banner; `Edit`/`Archive`/`COS`/`View on Map` hidden; tabs read-only
- [ ] Archive modal requires `archived_reason`; `archived_notes` required iff `other`; success toast + redirect hides record from default index
- [ ] Archived lot becomes available: `getPhasesWithAvailableLotsForCreate` lists the lot as `is_occupied=false`; map shows lot as available
- [ ] Recover succeeds when lot free → record returns to default index; lot re-occupied
- [ ] Recover blocked when lot now occupied by another active burial → `422 lot_id` error shown; record stays archived
- [ ] `activity_logs` contains `archived` / `restored` entries with `subject_type=BurialRecord`
- [ ] Map (`/data/burials`, `/data/partial-burials`, `/data/cluster/{id}/burials`), search (`/data-search/burials`), schedule (`/burial-schedules/events`), and reports all exclude archived by default

---

## Risk Notes

- **Lot contention on recover:** If a lot is reassigned between archive and recover, recovery must not silently steal the lot. Guard `active()->where('lot_id', $lotId)->exists()` blocks recovery; UX should prompt lot reassignment (future enhancement: recover modal with lot picker).
- **Enum ALTER on MySQL:** Extending `activity_logs.action` enum requires `MODIFY COLUMN` with full enum list — include all existing values + `archived,restored`. Test on staging dump first.
- **Route model binding on archived:** `BurialRecord $burial_record` binding uses `findOrFail($id)` without scope, so archived `show` still resolves — intentional. Do not add global `active()` scope to the model.
- **Admin visibility:** Admin `IndexView` shares `routeSearch` (`admin.burial_records.index`) — ensure admin can also filter `archived` but cannot archive/restore unless spec says so; enforce role check in controller if admin should be read-only.
- **No hard delete after archiving:** `destroy()` is deprecated. If a true purge is ever needed, add an admin-only `forceDelete` gated by policy + confirmation, separate from this plan.
- **Existing `DELETE` bookmarks:** If `DELETE /burial-records/{id}` is removed immediately, any open `ShowView` with stale JS will 405. Keep `destroy()` as deprecated alias for one release or return `410` with message `Use Archive instead.`
