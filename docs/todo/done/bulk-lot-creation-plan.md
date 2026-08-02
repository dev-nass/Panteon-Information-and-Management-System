# Bulk Lot Creation Refactor Plan

## Overview
Replace single-lot creation with a batch flow: user specifies `row` + `start column` → `end column`, then plots each lot sequentially on the map, and saves all at once.

---

## Current Flow (Before)
1. `CreateView.vue` Lot tab → manual `column` + `row` inputs
2. `LotPlottingModal.vue` → single marker placement
3. `useCreatePlotLot.js` composable → single coordinate capture
4. `storeLot` controller method → creates one lot per request

**Problem:** Creating 20 lots requires 20 separate form submissions, 20 modal opens, and manual naming each time.

---

## Target Flow (After)
1. User enters: `Row` (A), `Start Column` (1), `End Column` (5) → system knows it needs 5 lots: 1A, 2A, 3A, 4A, 5A
2. User opens bulk plotting modal → sees list of all lots + map
3. Click map for 1A → auto-advances to 2A → click for 2A → ... → click for 5A
4. User clicks "Save All" → one API call creates all lots atomically

---

## Files to Create

### 1. `resources/js/composables/lot_management/create/useBulkCreatePlotLot.js`
New composable handling bulk lot state and sequential plotting.

**Responsibilities:**
- Accept `row`, `startColumn`, `endColumn` → generate array: `[{ column: '1', row: 'A', coordinates: null }, ...]`
- Manage `currentIndex` tracking which lot is being plotted
- Reuse `initializeMap` / `loadCluster` pattern from existing composable
- On map click: set coordinates for current lot, auto-advance `currentIndex`
- Expose: `lots`, `currentIndex`, `currentLot`, `isComplete`, `plotCurrentLot(coords)`, `removeLot(index)`, `canSave`

---

### 2. `resources/js/Components/Map/BulkLotPlottingModal.vue`
New modal with sidebar lot list + map.

**Props:** `clusterId`, `phases`, `row`, `startColumn`, `endColumn`

**Layout:**
- **Left sidebar:** Scrollable list of lots (1A, 2A, 3A...) with status indicators:
  - Gray = not yet plotted
  - Green checkmark = plotted (shows coords)
  - Blue highlight = currently active
  - Delete button to un-plot a lot
- **Right:** Leaflet map (Google tiles, cluster boundary loaded)
- **Footer:** "Plotting: 2A of 5A" progress + "Save All Lots" button (disabled until all plotted)

**UX flow:**
1. Modal opens → map loads with cluster boundary → lot list shows 1A as active
2. User clicks marker on map → 1A gets coordinates, auto-advances to 2A
3. Repeat until all lots plotted
4. User clicks "Save All" → emits `bulkSave` event with full array

---

## Files to Modify

### 3. `resources/js/Pages/Shared/LotManagement/CreateView.vue`
Modify the Lot tab to support bulk creation.

**Changes:**
- Replace single `lotForm` with bulk form:
  ```js
  const lotBulkForm = useForm({
      cluster_id: '',
      row: '',
      start_column: '',
      end_column: '',
  })
  ```
- Replace column/row inputs with: `Row` (text, e.g. "A"), `Start Column` (number), `End Column` (number)
- Remove `status` field (lots default to `available`)
- "Plot on Map" button opens `BulkLotPlottingModal` instead
- On `bulkSave` event: POST to new `storeBulkLot` endpoint
- Remove `LotPlottingModal` import and related single-lot state

### 4. `app/Http/Controllers/Admin/LotManagementController.php`
Add new `storeBulkLot` method.

**Validation:**
```php
$validated = $request->validate([
    'cluster_id' => 'required|exists:clusters,id',
    'lots' => 'required|array|min:1',
    'lots.*.column' => 'required|string|max:255',
    'lots.*.row' => 'required|string|max:255',
    'lots.*.coordinates' => 'required|json',
]);
```

**Logic:**
- Check for duplicate `column` + `row` within the cluster
- Check for duplicates within the submitted batch itself
- Use `DB::transaction` to insert all lots atomically
- Return redirect with success message

### 5. `routes/admin.php`
Add new route after existing `storeLot`:
```php
Route::post('/lot-management/lot/bulk', 'storeBulkLot')->name('lot_management.store.bulk_lot');
```

---

## Files Unchanged
| File | Reason |
|------|--------|
| `useCreatePlotLot.js` | Still used by cluster/phase plotting |
| `LotPlottingModal.vue` | Kept as fallback for single-lot creation |
| `storeLot` controller method | Kept for backward compatibility |
| `Lot` model, migration, repository | No schema changes needed |

---

## Implementation Order
1. Create `useBulkCreatePlotLot.js` composable
2. Create `BulkLotPlottingModal.vue` component
3. Add `storeBulkLot` method to controller
4. Add bulk route to `routes/admin.php`
5. Update `CreateView.vue` lot tab to use bulk flow
6. Test full flow end-to-end
