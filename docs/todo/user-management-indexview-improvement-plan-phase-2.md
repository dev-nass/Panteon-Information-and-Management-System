# Admin User Management Improvement Plan — Phase 2

## Overview
Phase 2 adds a **dedicated ShowView page** for viewing, editing, and managing individual user accounts from the Admin User Management index. The page follows the BurialRecords ShowView pattern: header with action buttons, horizontal tabs, card container with `Display.vue` fields, and inline edit/save/discard flow. A third tab provides a quick list of burial records (and their associated deceased records) created by the user, with links to the full BurialRecord ShowView.

---

## Current State (After Phase 1)

| Area | Status |
|------|--------|
| `UserManagementController` | `index()`, `export()`, `destroy()` with guards, `applyFilters()` helper |
| `IndexView.vue` | Search, role filter, sortable table, verified badge, delete with confirmation, flash toasts, pagination, Invite Clerk + Export CSV buttons |
| User details | ❌ No way to view a user's full profile from the table |
| Edit user | ❌ No edit form — admin must ask users to update their own profile via Settings |
| Role management | ❌ Role is displayed but cannot be changed from this page |
| Burial record association | ❌ No way to see which records a user created |

**Context:** Users are created via Clerk Invitations (`role: 'clerk'`). `burial_records.user_id` tracks which user created each record (`nullOnDelete`). Admins may need to promote a clerk to head, correct profile details, or see which records a user is associated to. The BurialRecords ShowView already has an established pattern for view/edit pages with tabs, `Display.vue` components, and edit toggle — Phase 2 follows this pattern exactly.

---

## Target State (After)

### Page: `/admin/user-management/{user}`

**Layout (mirrors Shared/BurialRecords/ShowView.vue):**
- ← Back button → `admin.user_management.index`
- Header: user icon + full name + "User Account Details"
- Action buttons (role-gated):
  - **Edit** (pencil icon) — toggles `editing` mode (hidden for current user's own row)
  - **Save Changes** — visible when `editing && hasChanges`
  - **Discard** — visible when `editing`, resets changes via `confirm()` dialog
  - **Delete** (trash icon) — deletes user via `confirm()` dialog (same guards as index)
- Horizontal tab bar: **Profile** | **Account** | **Records**
- Card container with tab content

### Tab: Profile
Fields using `Display.vue` components (editable when in edit mode):
- First Name
- Middle Name
- Last Name
- Email (with verified badge inline when not editing)
- Contact Number
- Member Since (read-only, formatted date)

### Tab: Account
- Role (dropdown `<select>` when editing, badge when not editing)
- Email Verified At (read-only, formatted date or "Not verified")
- Burial Records Created (count, read-only)

### Tab: Records
A table listing burial records created by this user:
- Columns: ID, Deceased Name, Date of Burial, Phase, Cluster, Lot
- Each row is clickable → redirects to `admin.burial_records.show`
- Empty state: "No burial records created by this user"
- Sorted by most recent first (default burial record ordering)

---

## Backend Changes

### 1. Add `burialRecords()` relationship to User model

```php
// app/Models/User.php
public function burialRecords(): HasMany
{
    return $this->hasMany(BurialRecord::class);
}
```

### 2. `show()` method in UserManagementController

- Eager-load `burialRecords.deceasedRecord.lot.cluster.phase` for the Records tab
- Include `burial_records_count` via `withCount('burialRecords')` for the Account tab
- Return user data via Inertia render

```php
public function show(User $user)
{
    $user->loadCount('burialRecords');
    $user->load([
        'burialRecords.deceasedRecord',
        'burialRecords.lot.cluster.phase',
    ]);

    return Inertia::render('Admin/UserManagement/ShowView', [
        'user_data' => $user,
    ]);
}
```

Route: `GET /admin/user-management/{user}` → `admin.user_management.show`

### 3. `update()` method in UserManagementController

- Validation rules:
  - `first_name`: `required|string|max:255`
  - `middle_name`: `nullable|string|max:255`
  - `last_name`: `required|string|max:255`
  - `email`: `required|email|max:255|unique:users,email,{$user->id}`
  - `contact_number`: `required|string|max:20`
  - `role`: `required|in:clerk,head,admin`
- Guard: if `$user->id === $request->user()->id` and role is changing → `back()->with('error', 'You cannot change your own role.')`
- Update via `$user->update($validated)`
- Return redirect with `->with('success', 'User updated successfully.')`

Route: `POST /admin/user-management/{user}` → `admin.user_management.update`

---

## Frontend: ShowView.vue

### Script section (following BurialRecords/ShowView.vue pattern)

| Pattern | Source reference |
|---------|-----------------|
| Props | `user_data` object from controller |
| `editing` ref | `ref(false)` — toggle edit mode |
| `hasChanges` ref | `ref(false)` — tracks unsaved changes |
| `originalData` / `localData` | Deep copy of `user_data` for change detection |
| `watch([localData])` deep | Sets `hasChanges` via `isEqual` comparison |
| `activeTab` ref | `ref("profile")` — current tab |
| `tabs` array | `[{ key: 'profile', label: 'Profile' }, { key: 'account', label: 'Account' }, { key: 'records', label: 'Records' }]` |
| `back()` | `router.visit(route('admin.user_management.index'))` |
| `saveChanges()` | `router.post(route('admin.user_management.update', user.id), { ...localData.value }, { onSuccess, onError })` |
| `discardChanges()` | Uses `confirm()` to ask, then resets `localData` from `originalData`, `editing = false` |
| `deleteUser()` | Uses `confirm()` to ask, then `router.delete(route('admin.user_management.destroy', user.id))` |
| `viewRecord(record)` | `router.visit(route('admin.burial_records.show', record.id))` |
| `currentUser` | `computed(() => page.props.auth.user)` — for role-gating edit/delete |
| Toast | `useToast()` for success/error feedback |

### Template structure

```
<template>
  <div class="max-w-6xl mx-auto p-6">
    <!-- Back button -->
    <!-- Header: icon + name + subtitle -->
    <!-- Action buttons: Edit / Save / Discard / Delete -->

    <!-- Tab bar -->
    <!-- Card container -->
      <!-- Profile tab: Display fields -->
      <!-- Account tab: Role, Verified, Record count -->
      <!-- Records tab: Table with clickable rows -->
  </div>
</template>
```

### Records tab table

```html
<table v-if="localData.burial_records?.length > 0">
  <thead>
    <tr>
      <TableHeader>ID</TableHeader>
      <TableHeader>Deceased Name</TableHeader>
      <TableHeader>Date of Burial</TableHeader>
      <TableHeader>Phase</TableHeader>
      <TableHeader>Cluster</TableHeader>
      <TableHeader>Lot</TableHeader>
    </tr>
  </thead>
  <tbody>
    <tr v-for="record in localData.burial_records" :key="record.id"
        @click="viewRecord(record)"
        class="cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-700">
      <TableData>{{ record.id }}</TableData>
      <TableData>{{ record.deceased_record.first_name }} {{ record.deceased_record.last_name }}</TableData>
      <TableData>{{ record.deceased_record.date_of_depository }}</TableData>
      <TableData>{{ record.lot?.cluster?.phase?.phase_name }}</TableData>
      <TableData>{{ record.lot?.cluster?.cluster_name }}</TableData>
      <TableData>{{ record.lot?.column }}{{ record.lot?.row }}</TableData>
    </tr>
  </tbody>
</table>

<div v-else class="text-center py-12 text-gray-500 dark:text-neutral-400">
  No burial records created by this user.
</div>
```

---

## Files to Modify

| File | Change |
|------|--------|
| `routes/admin.php` | +2 routes: `show` (GET), `update` (POST) |
| `app/Models/User.php` | +`burialRecords()` HasMany relationship |
| `app/Http/Controllers/Admin/UserManagementController.php` | +`show()`, +`update()` methods |
| `resources/js/Pages/Admin/UserManagement/ShowView.vue` | **New file** — dedicated show/edit page |
| `resources/js/Pages/Admin/UserManagement/IndexView.vue` | Add "View" button (eye icon) in Actions column → `Link` to show page |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `users` migration | No schema changes |
| `BurialRecord` model | Already has `belongsTo(User::class)` |
| `BurialRecordController` | Show route untouched |
| `ClerkInvitationController` | Invitation flow untouched |
| `Display.vue` | Used as-is |

---

## Out of Scope
- **Password reset** — admin resetting a user's password requires a dedicated flow (email reset link or temporary password); the Account tab is ready to host this later
- **Account deactivation/status** — requires migration + middleware + login changes
- **User avatar/profile photo** — no avatar system exists in the schema
- **Audit log** — tracking who changed what is a future enhancement
- **Bulk role change** — changing roles for multiple users at once

---

## Implementation Order

1. Add `burialRecords()` relationship to `User` model
2. Add `show()` and `update()` to `UserManagementController` + routes in `admin.php`
3. Create `ShowView.vue` following BurialRecords ShowView pattern (header, tabs, edit toggle)
4. Implement Profile tab with `Display.vue` fields
5. Implement Account tab (role dropdown, verified status, record count)
6. Implement Records tab (table with clickable rows → `admin.burial_records.show`)
7. Add "View" button to `IndexView.vue` Actions column
8. Verify: `vendor/bin/pint`, `npx prettier --write`, `npm run build`, manual end-to-end test

---

## Risk Notes

- **Admin self-edit guard:** The edit button is hidden for the current user's own row. The `update()` method also guards against role self-change server-side.
- **Email uniqueness:** The validation rule `unique:users,email,{$user->id}` excludes the current user's own email.
- **Null user_id records:** Some burial records may have `user_id = null` (created before user tracking was added). The Records tab handles this gracefully — it only shows records where `user_id` matches.
- **Preserve state on save:** Use `preserveState: false` in the `router.post()` call so the page refreshes with updated data after saving (matching BurialRecords ShowView pattern).
