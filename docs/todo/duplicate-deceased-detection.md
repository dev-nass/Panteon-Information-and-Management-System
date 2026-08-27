# Plan: Duplicate Deceased Record Detection

## Goal

Prevent duplicate burial records from being created when a deceased person with the same name and dates already exists in the system. Applies to the Clerk form and Admin import. The seeder is excluded.

---

## 1. Current State

| Piece | Location | Notes |
|---|---|---|
| Clerk create form | `resources/js/Pages/Shared/BurialRecords/CreateView.vue` | No duplicate check |
| Clerk controller | `app/Http/Controllers/Clerk/BurialRecordController.php` | Calls `BurialRecordService::store()` |
| Clerk form request | `app/Http/Requests/Clerk/BurialRecordStoreRequest.php` | Validates fields only, no DB lookup |
| Clerk service | `app/Services/BurialRecordService.php` | Creates applicant → deceased → burial record in a transaction |
| Clerk repository | `app/Repositories/DeceasedRecordRepository.php` | `createDeceasedRecord()` — no uniqueness check |
| Admin import | `app/Http/Controllers/Admin/ImportingController.php` | Does a duplicate check on `first_name + last_name + date_of_depository` only (line 103-106), but misses `date_of_birth` / `date_of_death` |
| Seeder | `database/seeders/PanteonDataSeeder.php` | Excluded — imports historical data as-is |

### What the Admin import already does (partial)

```php
// ImportingController.php lines 103-106
$existingRecord = DeceasedRecord::where('first_name', $deceasedData['first_name'])
    ->where('last_name', $deceasedData['last_name'])
    ->where('date_of_depository', $deceasedData['date_of_depository'])
    ->first();
```

This checks name + burial date but misses `date_of_birth` and `date_of_death`. Two different people with the same name buried on the same date would be flagged as duplicates even though they're different individuals.

---

## 2. Duplicate Detection Logic

### Matching criteria

A record is considered a **duplicate** when **all three** match:

1. `first_name` — exact match (after normalization/trim)
2. `last_name` — exact match (after normalization/trim)
3. `date_of_birth` **OR** `date_of_death` — at least one matches

Using OR for dates means: if two records share a name and *either* the same birth date *or* the same death date, it's flagged. This catches most real duplicates while allowing different people with the same name (different dates) to coexist.

### Why not use `date_of_depository` (burial date)?

Two different people with the same name could be buried on the same date. Birth/death dates are stronger identity signals.

---

## 3. Backend Changes

### 3.1 `RecordNormalizationService` — new method

```php
/**
 * Check if a deceased record with the same name and dates already exists.
 */
public function findDuplicateDeceased(
    string $firstName,
    string $lastName,
    ?string $dateOfBirth,
    ?string $dateOfDeath
): ?DeceasedRecord
```

Logic:
- Query `DeceasedRecord` where `first_name` = trimmed input AND `last_name` = trimmed input
- AND (`date_of_birth` = input OR `date_of_death` = input) — only include date conditions when the values are non-null
- Return the matching record or `null`

### 3.2 `DeceasedRecordRepository::createDeceasedRecord()`

Before creating, call `findDuplicateDeceased()`. If a match is found, throw a `ValidationException` with a descriptive message:

```php
if ($duplicate) {
    throw ValidationException::withMessages([
        'first_name' => "A record for {$firstName} {$lastName} already exists (ID: {$duplicate->id}).",
    ]);
}
```

This integrates with Laravel's existing validation flow — the error appears as a field-level validation error on the form.

### 3.3 `ImportingController::store()`

Replace the existing duplicate check (lines 103-106) with a call to `findDuplicateDeceased()`. When found, skip the row and add to `$errors[]` as before.

---

## 4. Frontend Changes

No changes needed. The Vue form already displays validation errors from the server via `form.errors.*`. When the backend throws a `ValidationException`, the error will appear under the relevant field automatically.

---

## 5. Files to Modify

| File | Change |
|---|---|
| `app/Services/RecordNormalizationService.php` | Add `findDuplicateDeceased()` method |
| `app/Repositories/DeceasedRecordRepository.php` | Call duplicate check before `create()`, throw `ValidationException` if found |
| `app/Http/Controllers/Admin/ImportingController.php` | Replace inline duplicate check with `findDuplicateDeceased()` call |

### Files NOT modified

- `PanteonDataSeeder.php` — historical data import, no duplicate detection
- `BurialRecordService.php` — no changes needed (repository handles the check)
- `BurialRecordStoreRequest.php` — no changes needed (validation stays field-level)
- `CreateView.vue` — no changes needed (errors display automatically)

---

## 6. Tests (Pest)

New test in `tests/Feature/Clerk/BurialRecordDuplicateTest.php`:

- `it_blocks creation when same name and date_of_birth already exists`
- `it_blocks creation when same name and date_of_death already exists`
- `it_allows creation when name matches but dates differ`
- `it_allows creation when dates match but name differs`

Update existing import tests if any exist for `ImportingController`.

---

## 7. Verification

```bash
php artisan test --compact --filter=BurialRecordDuplicate
vendor/bin/pint --dirty --format agent
```

Manual test: create a record via the clerk form, then try to create another with the same name + dates — expect a validation error.

---

## 8. Out of Scope

- Address standardization (separate plan — barangay-dropdown-address-standardization.md)
- Fuzzy name matching (e.g., "Juan" vs "Juanito") — exact match only
- Cross-referencing applicant records for duplicate detection
