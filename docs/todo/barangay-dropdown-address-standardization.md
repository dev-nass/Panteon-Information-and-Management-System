# Plan: Barangay Dropdown for Address Standardization

## Goal

Replace the free-text address input on the Clerk burial record form with a dropdown of the 75 official Dasmariñas, Cavite barangays. This eliminates typos and ensures every address is stored in a normalized, consistent format. An "Other" option with free-text fallback covers non-Dasma addresses.

---

## 1. Current State

| Piece | Location | Notes |
|---|---|---|
| Clerk form | `resources/js/Pages/Shared/BurialRecords/CreateView.vue` (lines 388-405) | Plain `<Input>` text field for address |
| Form request | `app/Http/Requests/Clerk/BurialRecordStoreRequest.php` | `'address' => 'required\|string\|max:255'` |
| Repository | `app/Repositories/DeceasedRecordRepository.php` | Stores address via `RecordNormalizationService::normalizeAddress()` |
| Normalization | `app/Services/RecordNormalizationService.php` | Title-cases and trims address |
| Admin import | `app/Http/Controllers/Admin/ImportingController.php` | Reads address from spreadsheet, title-cases it — **unchanged** |
| Seeder | `database/seeders/PanteonDataSeeder.php` | Reads address from Excel — **unchanged** |
| DB column | `deceased_records.address` | `string`, nullable, 12,237 records populated |

### Existing address data quality (from DB analysis)

- 647 distinct raw address values → ~80 real Dasmariñas barangays, rest are typos/garbage
- Top barangays: Paliparan 3 (1,329), Salawag (927), Sampaloc 4 (813), Victoria Reyes (382)
- ~22% of addresses are misspelled variants of known barangays

---

## 2. Data Source

### Static JSON file: `public/data/barangays.json`

Download from `https://barangays.sanchez.ph/downloads/barangays.json` (MIT license, PSGC-sourced) and filter to Dasmariñas city code `042107`. The file will contain:

```json
[
  { "code": "042107001", "name": "Burol I" },
  { "code": "042107002", "name": "Burol II" },
  ...
]
```

75 entries total. Stored as a static file — no runtime API dependency. Updated manually if Dasma creates new barangays (rare).

---

## 3. Backend Changes

### 3.1 New route for barangay data

Add a route that serves the barangay list to the frontend:

```
GET /api/barangays → returns the JSON file contents
```

**Route file:** `routes/web.php` (or `routes/api.php` if no API prefix preferred)

### 3.2 `DeceasedRecordRepository` — no changes

The repository already normalizes address via `RecordNormalizationService::normalizeAddress()`. With a dropdown, the value is already clean — normalization becomes a no-op (just trims/title-cases).

### 3.3 `RecordNormalizationService::normalizeAddress()` — no changes

Still useful as a safety net for the "Other" free-text option and for the admin import/seeder paths.

---

## 4. Frontend Changes (`CreateView.vue`)

### 4.1 Replace address `<Input>` with `<Select>` + conditional `<Input>`

```vue
<!-- Barangay dropdown -->
<Select v-model="form.address" :options="barangayOptions" placeholder="Select barangay" />

<!-- Shown only when "Other" is selected -->
<Input v-if="form.address === 'Other'" v-model="form.otherAddress" placeholder="Enter address" />
```

### 4.2 Fetch barangay list on mount

```js
const barangays = ref([]);

onMounted(async () => {
    const res = await fetch('/api/barangays');
    barangays.value = await res.json();
});
```

### 4.3 Compute barangay options

```js
const barangayOptions = computed(() => {
    const names = barangays.value.map(b => b.name);
    return [...names.sort(), 'Other'];
});
```

### 4.4 Handle "Other" submission

When `form.address === 'Other'`, submit `form.otherAddress` instead:

```js
// Before submit, resolve "Other" to the free-text value
if (form.address === 'Other') {
    form.address = form.otherAddress;
}
```

### 4.5 Display selected barangay on edit/show

The stored value is the barangay name string (e.g., "Paliparan 3"), so the show page needs no changes — it already displays `deceasedRecord.address`.

---

## 5. Files to Create

| File | Purpose |
|---|---|
| `public/data/barangays.json` | Static list of 75 Dasma barangays (downloaded + filtered from PSGC) |

## 6. Files to Modify

| File | Change |
|---|---|
| `resources/js/Pages/Shared/BurialRecords/CreateView.vue` | Replace address `<Input>` with `<Select>` + conditional "Other" input |
| `routes/web.php` or `routes/api.php` | Add route to serve barangays.json |

### Files NOT modified

- `app/Http/Controllers/Admin/ImportingController.php` — import stays as-is
- `database/seeders/PanteonDataSeeder.php` — seeder stays as-is
- `app/Repositories/DeceasedRecordRepository.php` — no changes needed
- `app/Services/RecordNormalizationService.php` — no changes needed
- `app/Http/Requests/Clerk/BurialRecordStoreRequest.php` — validation stays the same (string max:255)

---

## 7. Tests (Pest)

New test in `tests/Feature/Clerk/BarangayDropdownTest.php`:

- `it returns the barangay list from the API endpoint`
- `it contains all 75 Dasma barangays`
- `it allows submitting a valid barangay name via the clerk form`
- `it allows submitting a free-text address when Other is selected`

---

## 8. Verification

```bash
php artisan test --compact --filter=BarangayDropdown
vendor/bin/pint --dirty --format agent
```

Manual test: open the clerk create form, verify the dropdown shows 75 sorted barangays + "Other", select a barangay, submit, confirm the stored address matches exactly.

---

## 9. Out of Scope

- Admin import validation (stays as-is per user decision)
- Seeder changes (historical data)
- Block/lot/house number sub-addresses (user chose barangay-only)
- Auto-migration of existing 12,237 records to match the dropdown values (separate cleanup task)
