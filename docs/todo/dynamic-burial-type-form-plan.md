# Dynamic Burial Record Form Based on Burial Type

## Overview

When the clerk clicks "Create" on the Index, they're prompted to select a burial type (Normal, Muslim, Cremation). The Create form then adjusts which fields are required based on the selected type.

---

## 1. Database Migration — Update `corpse_disposal` enum

**Action:** Create a new migration to update the `corpse_disposal` enum.

Change the enum from `['burial', 'cremation', 'other']` to `['burial', 'muslim', 'cremation']`:

```php
$table->enum('corpse_disposal', ['burial', 'muslim', 'cremation'])->nullable();
```

---

## 2. Index Page — Add Type Selection Modal

**File:** `resources/js/Pages/Shared/BurialRecords/IndexView.vue`

- Replace the single `<Link>` Create button with a `<Button>` that opens a modal
- The modal displays 3 selectable cards/buttons: **Normal Burial**, **Muslim Burial**, **Cremation**
- On selection, navigate to the Create page with a query param: `clerk.burial_records.create?type=burial` (or `muslim`, `cremation`)

---

## 3. Create Page — Dynamic Form Based on Type

**File:** `resources/js/Pages/Shared/BurialRecords/CreateView.vue`

### Changes

- Read `type` from `$route.query.type` on mount, pre-set `form.corpse_disposal` to that value
- Add a visible "Burial Type" display/read-only field at the top of the form (or a small badge) so the clerk knows which type they're filling out
- Make the "Corpse Disposal" field a `<select>` dropdown instead of free text, with the 3 options hardcoded, pre-selected based on query param
- Add a `computed` or `ref` for `isCremation` based on `form.corpse_disposal === 'cremation'`
- Conditionally apply `required` attribute on:
  - `birth_date` — required when cremation
  - `cremation_place` — required when cremation
  - `cremation_date` — required when cremation
- If the clerk changes the type dropdown mid-form, update the dynamic requirements accordingly

---

## 4. Backend Validation — Conditional Rules

**File:** `app/Http/Requests/Clerk/BurialRecordStoreRequest.php`

Make validation rules conditional based on `corpse_disposal` value:

```php
public function rules(): array
{
    $corpseDisposal = $this->input('corpse_disposal');

    return [
        // ... all existing rules remain the same ...

        // Cremation-specific required fields
        'birth_date' => $corpseDisposal === 'cremation'
            ? 'required|date'
            : 'nullable|date',

        'cremation_place' => $corpseDisposal === 'cremation'
            ? 'required|string|max:255'
            : 'nullable|string|max:255',

        'cremation_date' => $corpseDisposal === 'cremation'
            ? 'required|date'
            : 'nullable|date',
    ];
}
```

---

## 5. Summary of Field Requirements by Type

| Field | Normal (`burial`) | Muslim (`muslim`) | Cremation (`cremation`) |
|-------|-------------------|-------------------|-------------------------|
| All personal/death fields | Same as current | Same as current | Same as current |
| `birth_date` | optional | optional | **required** |
| `cremation_place` | optional | optional | **required** |
| `cremation_date` | optional | optional | **required** |
| `corpse_disposal` | `burial` | `muslim` | `cremation` |
| Lot selection | required | required | required |

---

## 6. Files to Modify

| # | File | Action |
|---|------|--------|
| 1 | `database/migrations/xxxx_xx_xx_update_deceased_records_corpse_disposal_enum.php` | **Create** — new migration to update enum |
| 2 | `resources/js/Pages/Shared/BurialRecords/IndexView.vue` | **Edit** — add type selection modal |
| 3 | `resources/js/Pages/Shared/BurialRecords/CreateView.vue` | **Edit** — read type from query, dynamic required fields, select dropdown |
| 4 | `app/Http/Requests/Clerk/BurialRecordStoreRequest.php` | **Edit** — conditional validation rules |
| 5 | `app/Http/Resources/DeceasedRecordResource.php` | **Check** — may need to handle `muslim` type in conditional expose logic |
