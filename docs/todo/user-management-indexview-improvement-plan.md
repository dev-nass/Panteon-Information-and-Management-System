# Admin User Management Improvement Plan

## Overview
Improve the Admin `UserManagement` IndexView feature. Currently the page only supports search, role filter, sorting, pagination, and account deletion — with **no visual feedback after actions** and **no safety guards** on delete. This plan adds baseline fixes (toasts, delete guards) plus quality-of-life extras (verified badge, invite shortcut, CSV export).

---

## Current State (Before)

| Area | Status |
|------|--------|
| `UserManagementController::index()` | Search (name/email), role filter, sort, paginate(10) ✅ |
| `UserManagementController::destroy()` | Hard delete, **no guards** ⚠️ |
| `IndexView.vue` | Search box, filter dropdown (All/Admin/Head/Clerk), sortable table, delete + HSOverlay confirmation modal, pagination |
| Action feedback | ❌ `destroy()` redirects with flash, but IndexView never renders it (no `page.props.flash` watcher) |
| Other actions | ❌ No view details, no edit, no role management, no verification status |

**Context:** Users are created via Clerk Invitations (`role: 'clerk'`, `email_verified_at` set at registration). `burial_records.user_id` is `nullOnDelete`, so deleting a user is data-safe but loses record attribution. All user table fields are already present in the page payload — verification status can be rendered 100% client-side.

---

## Target State (After)

### Phase 1 — Baseline Fixes

#### 1. Flash-toast feedback (`IndexView.vue`)
- Add `useToast` + `watch(page.props.flash)` → `toast.success(flash.success)` / `toast.error(flash.error)`
- Copy the existing pattern from `ImportRecord/IndexView.vue:28-38` and `ClerkInvitation/CreateView.vue:22-30`
- Delete success/errors become visible to the admin

#### 2. Delete guards (`UserManagementController::destroy()` + `IndexView.vue`)
Backend:
- `$user->id === $request->user()->id` → `back()->with('error', 'You cannot delete your own account.')`
- `$user->role === 'admin'` → `back()->with('error', 'Admin accounts cannot be deleted.')`

Frontend:
- Delete button hidden for admin rows (`user.role === 'admin'`) and for the current user's own row (`user.id === page.props.auth.user.id`)
- Confirmation modal shows the user's **full name** and warns that burial-record attribution (`burial_records.user_id`, `nullOnDelete`) will be cleared

### Phase 3 — Extras

#### 3. Verified badge column (`IndexView.vue`)
- New "Verification" column between Role and Actions
- Green "Verified" badge when `email_verified_at` is set, amber "Pending" otherwise
- Empty-state `colspan` updated from 6 → 7

#### 4. "Invite Clerk" button (`IndexView.vue`)
- Header button (next to Filter) linking to `route('admin.clerk_invitations.create')`
- Reuses existing header button styling

#### 5. CSV export (`UserManagementController::export()` + route + button)
- Refactor the search/filter/sort builder from `index()` into a private `applyFilters($query)` helper
- New `export(Request $request)`: reuse `applyFilters`, drop pagination, return FastExcel download
- Columns: ID, First Name, Middle Name, Last Name, Email, Contact Number, Role, Verified, Member Since
- Filename: `users_YYYY-MM-DD.csv` (FastExcel already used in `GenerateReportController`)
- Route: `GET /admin/user-management/export` → `admin.user_management.export`
- Frontend: "Export CSV" button in the header → `router.get` to the export route carrying current `search/filter/sort_field/sort_direction` so the export matches the visible filtered view

---

## Files to Modify

| File | Change |
|------|--------|
| `routes/admin.php` | +1 GET route (`user_management.export`) |
| `app/Http/Controllers/Admin/UserManagementController.php` | Delete guards, `applyFilters()` refactor, `export()` method |
| `resources/js/Pages/Admin/UserManagement/IndexView.vue` | Flash-toast watcher, delete button guards, verified column, Invite Clerk + Export CSV buttons |

---

## Files Unchanged
| File | Reason |
|------|--------|
| `User` model, users migration | No schema changes needed |
| `ClerkInvitationController` | Invitation flow untouched |

---

## Out of Scope
- View user details modal / edit user form / role management (Phase 2) — explicitly excluded per user decision
- Account deactivation (status column) — requires migration + middleware + login changes; hard delete with guards covers the current need

---

## Implementation Order
1. Add flash-toast watcher to `IndexView.vue`
2. Add delete guards to controller + hide delete buttons in UI + improve confirmation modal
3. Add verified badge column
4. Add "Invite Clerk" header button
5. Refactor `applyFilters()` + add `export()` + route + "Export CSV" button
6. Verify: `vendor/bin/pint`, `npx prettier --write`, `npm run build`, manual end-to-end test
