# User Management CSV Export — Enhancement Plan

## Overview
Enhance the existing CSV export with three improvements: an "Export All" button that bypasses filters, a single-user export from ShowView, and a "Burial Records Count" column. Uses the existing FastExcel package.

---

## Current State

| Area | Status |
|------|--------|
| `UserManagementController::export()` | Exports filtered users via `applyFilters()` |
| Export columns | ID, First Name, Middle Name, Last Name, Email, Contact Number, Role, Verified, Member Since |
| Export trigger | "Export CSV" button in IndexView → applies current search/filter/sort |
| ShowView | ❌ No export option for a single user |
| Export All | ❌ No way to export full user list independent of filters |

**Context:** The current export respects the active search/filter/sort state on the page. Admins sometimes need a full dump of all users regardless of what's filtered. Additionally, exporting a single user's profile from ShowView is useful for record-keeping or sharing.

---

## Target State

### 1. Export All (IndexView)

- New "Export All" button in the IndexView header, placed next to the existing "Export CSV" button
- "Export All" hits a new route `GET /admin/user-management/export-all` → `admin.user_management.export_all`
- Bypasses all filters — exports every user in the system
- Same CSV columns as the filtered export + new "Burial Records Count" column
- Filename: `users_all_YYYY-MM-DD.csv`

### 2. Filtered Export (IndexView — existing, enhanced)

- The existing "Export CSV" button continues to export the filtered view
- Add "Burial Records Count" column to the output
- Filename stays: `users_YYYY-MM-DD.csv`

### 3. Single User Export (ShowView)

- New "Export CSV" button in the ShowView header action buttons (visible alongside Edit/Delete when not editing)
- Hits a new route `GET /admin/user-management/{user}/export` → `admin.user_management.export_single`
- Exports a single-row CSV for that user
- Same columns as the bulk export
- Filename: `user_{id}_YYYY-MM-DD.csv`

### 4. New CSV Column

- **Burial Records Count** — `withCount('burialRecords')` on the query, included as a column in all three export variants

---

## Backend Changes

### 1. Extract `mapUserToRow()` helper

Refactor the repeated column mapping into a private method:

```php
private function mapUserToRow(User $user): array
{
    return [
        'ID' => $user->id,
        'First Name' => $user->first_name,
        'Middle Name' => $user->middle_name,
        'Last Name' => $user->last_name,
        'Email' => $user->email,
        'Contact Number' => $user->contact_number,
        'Role' => $user->role,
        'Verified' => $user->email_verified_at ? 'Yes' : 'No',
        'Burial Records Count' => $user->burial_records_count ?? 0,
        'Member Since' => $user->created_at?->format('Y-m-d'),
    ];
}
```

### 2. Update existing `export()` to use helper + withCount

```php
public function export(Request $request)
{
    $query = $this->applyFilters($request, User::query()->withCount('burialRecords'));

    $users = $query->get()->map(fn ($user) => $this->mapUserToRow($user));

    $filename = 'users_' . date('Y-m-d') . '.csv';

    return (new FastExcel($users))->download($filename);
}
```

### 3. `exportAll()` in `UserManagementController`

```php
public function exportAll()
{
    $users = User::withCount('burialRecords')
        ->latest()
        ->get()
        ->map(fn ($user) => $this->mapUserToRow($user));

    $filename = 'users_all_' . date('Y-m-d') . '.csv';

    return (new FastExcel($users))->download($filename);
}
```

### 4. `exportSingle()` in `UserManagementController`

```php
public function exportSingle(User $user)
{
    $user->loadCount('burialRecords');

    $row = collect([$this->mapUserToRow($user)]);

    $filename = "user_{$user->id}_" . date('Y-m-d') . '.csv';

    return (new FastExcel($row))->download($filename);
}
```

### 5. Routes (`admin.php`)

```php
Route::controller(UserManagementController::class)->group(function () {
    Route::get('/user-management', 'index')->name('user_management.index');
    Route::get('/user-management/export', 'export')->name('user_management.export');
    Route::get('/user-management/export-all', 'exportAll')->name('user_management.export_all');
    Route::get('/user-management/{user}/export', 'exportSingle')->name('user_management.export_single');
    Route::get('/user-management/{user}', 'show')->name('user_management.show');
    Route::post('/user-management/{user}', 'update')->name('user_management.update');
    Route::delete('/user-management/{user}', 'destroy')->name('user_management.destroy');
});
```

**Route ordering note:** `export-all` must be defined before `{user}` to avoid the literal "export-all" being captured as a `{user}` wildcard.

---

## Frontend Changes

### IndexView.vue — "Export All" button

Add a second button next to the existing "Export CSV" button:

```vue
<Button type="button" @click="exportAllUsers">
    <svg><!-- download icon --></svg>
    <span class="dark:text-white">Export All</span>
</Button>
```

New function:

```js
const exportAllUsers = () => {
    router.get(route("admin.user_management.export_all"), {}, {
        preserveState: true,
    });
};
```

### ShowView.vue — "Export CSV" button

Add an "Export CSV" button in the header action area (when not editing):

```vue
<a
    v-if="!editing"
    :href="route('admin.user_management.export_single', localData.id)"
    class="flex items-center justify-center gap-x-2 px-4 py-2 rounded-xl border border-transparent dark:text-white dark:bg-neutral-800 hover:dark:bg-neutral-600 transition-all duration-200"
>
    <svg><!-- download icon --></svg>
    Export CSV
</a>
```

Uses a direct `<a>` tag (not `router.get`) since this is a file download — no page navigation occurs.

---

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/Admin/UserManagementController.php` | Add `exportAll()`, `exportSingle()`, `mapUserToRow()` helper; update `export()` to use helper + `withCount` |
| `routes/admin.php` | +2 routes: `export_all` (GET), `export_single` (GET) |
| `resources/js/Pages/Admin/UserManagement/IndexView.vue` | Add "Export All" button + `exportAllUsers()` function |
| `resources/js/Pages/Admin/UserManagement/ShowView.vue` | Add "Export CSV" button (direct `<a>` link) |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `User` model | No schema changes; `withCount` is a query-level feature |
| `FastExcel` package | Already installed, used as-is |
| `Display.vue` | Unaffected |

---

## Implementation Order

1. Extract `mapUserToRow()` helper in `UserManagementController`
2. Update existing `export()` to use helper + add `withCount('burialRecords')`
3. Add `exportAll()` method
4. Add `exportSingle()` method
5. Add routes to `admin.php` (order: `export`, `export_all`, `{user}/export`, `{user}`)
6. Add "Export All" button to `IndexView.vue`
7. Add "Export CSV" button to `ShowView.vue`
8. Verify: `vendor/bin/pint`, `npx prettier --write`, `npm run build`

---

## Risk Notes

- **Route ordering:** `export-all` and `{user}/export` use different path structures (`/export-all` vs `/{user}/export`), so there's no ambiguity. However, `export-all` should still be listed before `{user}` routes as a safety measure.
- **Large datasets:** Exporting all users with `withCount` is a single query with a subquery — efficient for typical admin panel sizes (hundreds to low thousands of users). If the user base grows significantly, consider queueing the export.
- **Direct `<a>` for single export:** Using `<a href>` instead of `router.get` ensures the browser handles the file download response correctly without Inertia intercepting it as a page visit.
- **CSV encoding:** FastExcel handles UTF-8 encoding by default, which covers Filipino characters (ñ, etc.) in names.
