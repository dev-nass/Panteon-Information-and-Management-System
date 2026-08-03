# Multi-Type Import Plan (Normal, Muslim, Columbarium)

## Overview

The admin selects an import type (Normal, Muslim, Columbarium) before uploading a spreadsheet. The backend parses columns differently based on the selected type. Columbarium uses a hardcoded phase (`"clbm"`, id: 9).

---

## 1. Frontend — Add Import Type Selection

**File:** `resources/js/Pages/Admin/ImportRecord/IndexView.vue`

### Changes

- Add a type selection step before file upload with 3 cards: **Normal**, **Muslim**, **Columbarium**
- Store selected type in a `ref` (e.g., `importType`)
- Show which type is currently selected above the file upload area
- Pass `import_type` along with the file in the `FormData` POST request
- Optionally display a column reference guide below the upload showing expected columns for the selected type

---

## 2. Backend — Accept `import_type` Parameter

**File:** `app/Http/Controllers/Admin/ImportingController.php`

### Changes to `store()` method

- Validate `import_type` is present and one of: `normal`, `muslim`, `columbarium`
- Route row parsing to a type-specific method based on `import_type`

```php
$validated = $validator->validate();
$importType = $validated['import_type'];

// In the loop:
$rowData = match($importType) {
    'normal' => $this->parseNormalRow($row),
    'muslim' => $this->parseMuslimRow($row),
    'columbarium' => $this->parseColumbariumRow($row),
};
```

---

## 3. Column Mappings by Type (0-indexed)

### Normal (current behavior — unchanged)

| Index | Column           | Maps To                            |
|-------|------------------|------------------------------------|
| 0     | NO.              | (ignored)                          |
| 1     | BURIAL DATE      | `DeceasedRecord.date_of_depository`|
| 2     | NAME OF DECEASED | `DeceasedRecord.first_name`, `last_name` |
| 3     | APPLICANT        | `Applicant.first_name`, `last_name`|
| 4     | PHASE            | Lot lookup                         |
| 5     | CLUSTER          | Lot lookup                         |
| 6     | APT. NUMBER      | Lot lookup                         |
| 7     | BRGY/ADDRESS     | `DeceasedRecord.address`           |

### Columbarium

| Index | Column               | Maps To                            |
|-------|----------------------|------------------------------------|
| 0     | NO.                  | (ignored)                          |
| 1     | NAME OF DECEASED     | `DeceasedRecord.first_name`, `last_name` |
| 2     | ADDRESS              | `DeceasedRecord.address`           |
| 3     | BIRTHDATE (MM/DD/YY) | `DeceasedRecord.date_of_birth`     |
| 4     | DATE OF DEATH        | `DeceasedRecord.date_of_death`     |
| 5     | DATE OF CREMATION    | `DeceasedRecord.cremation_date`    |
| 6     | DATE OF DEPOSITORY   | `DeceasedRecord.date_of_depository`|
| 7     | PLACE OF CREMATION   | `DeceasedRecord.cremation_place`   |
| 8     | NAME OF APPLICANT    | `Applicant.first_name`, `last_name`|
| 9     | RELATIONSHIP         | `Applicant.relationship`           |
| 10    | CONTACT NUM          | `Applicant.contact_number`         |
| 11    | CLUSTER              | Lot lookup (via `Cluster.cluster_name`) |
| 12    | APARTMENT            | Lot lookup (via `Lot.column`, `Lot.row`) |

> **Phase is hardcoded to `"clbm"` for columbarium.**

### Muslim

| Index | Column             | Maps To                            |
|-------|--------------------|------------------------------------|
| 0     | NO.                | (ignored)                          |
| 1     | DATE OF APPLICANT  | (disregarded — no column in DB)    |
| 2     | NAME OF DECEASED   | `DeceasedRecord.first_name`, `last_name` |
| 3–5   | (not used)         | —                                  |
| 6     | NAME OF APPLICANT  | `Applicant.first_name`, `last_name`|
| 7–9   | (not used)         | —                                  |
| 10    | PHASE              | Lot lookup                         |
| 11    | CLUSTER            | Lot lookup                         |
| 12    | APARTMENT NO.      | Lot lookup                         |

---

## 4. Lot Lookup Logic

All three types use **Phase + Cluster + Apartment**:

```php
$column = preg_replace('/\D/', '', $aptNumber);
$rowLetter = preg_replace('/\d/', '', $aptNumber);

$lot = Lot::where('column', $column)
    ->where('row', $rowLetter)
    ->whereHas('cluster', fn($q) => $q->where('cluster_name', $clusterName)
        ->whereHas('phase', fn($q) => $q->where('phase_name', $phaseName)))
    ->whereDoesntHave('burialRecords')
    ->first();
```

For **Columbarium**, `$phaseName` is always `"clbm"`.

---

## 5. Refactored Controller Structure

Extract type-specific parsing into dedicated methods:

```php
private function parseNormalRow(array $row): array
private function parseColumbariumRow(array $row): array
private function parseMuslimRow(array $row): array
```

Each returns a normalized array:

```php
[
    'deceased' => [
        'first_name', 'middle_name', 'last_name',
        'address', 'date_of_birth', 'date_of_death',
        'date_of_depository', 'cremation_date', 'cremation_place',
    ],
    'applicant' => [
        'first_name', 'middle_name', 'last_name',
        'contact_number', 'relationship',
    ],
    'lot' => ['phase_name', 'cluster_name', 'apt_number'],
]
```

The `store()` method then uses this normalized data to create records (shared logic for all types).

---

## 6. Files to Modify

| # | File | Action |
|---|------|--------|
| 1 | `resources/js/Pages/Admin/ImportRecord/IndexView.vue` | **Edit** — add import type selection UI, pass `import_type` in FormData |
| 2 | `app/Http/Controllers/Admin/ImportingController.php` | **Edit** — add type validation, refactor to type-specific parse methods, hardcode columbarium phase |

---

## 7. Summary of Key Differences

| Aspect | Normal | Columbarium | Muslim |
|--------|--------|-------------|--------|
| Phase | From spreadsheet | Hardcoded `"clbm"` | From spreadsheet |
| Cluster | From spreadsheet | From spreadsheet | From spreadsheet |
| Apartment | From spreadsheet | From spreadsheet | From spreadsheet |
| Cremation fields | Not mapped | Mapped | Not mapped |
| Birth date | Not mapped | Mapped | Not mapped |
| Date of death | Not mapped | Mapped | Not mapped |
| Applicant relationship | Not mapped | Mapped | Not mapped |
| Applicant contact | Not mapped | Mapped | Not mapped |
| Date of applicant | N/A | N/A | Disregarded |
