# User Management — Terminate Instead of Delete (Plan)

Mirrors `burial-record-archiving-plan.md:1` + `database/migrations/2026_09_13_000001_add_archiving_to_burial_records_table.php:1` — no separate archive table.

---

## Overview

Replace hard deletion of `users` (`app/Http/Controllers/Admin/UserManagementController.php:87` `destroy()` + `User::delete()`) with a **Terminate** workflow. Terminated accounts are hidden from the default User Management table and only appear when a specific `Terminated` filter is applied. On `ShowView`, a terminated account shows a single green-accented `Reinstate` button instead of the normal action set (`Edit`, `Terminate`). Uses new columns on `users` — no separate terminated table.

---

## Current State

| Area | Status |
|------|--------|
| `app/Models/User.php:8` | `HasFactory,Notifiable`, `$fillable=[first_name,middle_name,last_name,contact_number,email,role]`, `casts email_verified_at/password`, `hasMany burialRecords/importLogs`, **no `terminated_*`**, no `SoftDeletes` (17 migrations: 0 `softDeletes()` per `todo.md:20`) |
| `database/migrations/0001_01_01_000000_create_users_table.php:10` | `id,first_name,middle_name?,last_name,email! unique, email_verified_at?,password,contact_number,role enum(clerk,head,admin),rememberToken,timestamps` — FK `burial_records.user_id nullable nullOnDelete` so hard delete orphans attribution |
| `app/Http/Controllers/Admin/UserManagementController.php:87` | `destroy()` hard `$user->delete()` with guards `self-delete` + `admin role` block, `logActivity('deleted')`, `redirect()->route('admin.user_management.index')`. `index()` `applyFilters` search concat + role filter, `show()` loads `BurialRecordService::index(..., $user->id)` + `user_data`, `update()` role change guard |
| `routes/admin.php:41` | `GET /user-management` `index`, `GET /export`, `GET /{user}` `show`, `POST /{user}` `update`, `DELETE /{user}` `destroy` — all `['auth','verified','admin']` via `bootstrap/app.php:15` `AdminMiddleware.php:11` (`role!==admin →403`) |
| `resources/js/Pages/Admin/UserManagement/IndexView.vue:1` | `filter all\|admin\|head\|clerk` radio + `search` debounced `router.get(...,{filter,search})`, rows click → `show`, no delete; `Export CSV`, `Invite Clerk` |
| `resources/js/Pages/Admin/UserManagement/ShowView.vue:110` | `canDelete = id!==me && role!=='admin'`, `canEdit = id!==me`, `#delete-user-modal` `hs-overlay` red icon “attribution … will be cleared” → `router.delete(route('admin.user_management.destroy'))` `onSuccess HSOverlay.close + $toast + router.visit(index)` |
| `app/Http/Controllers/Auth/LoginController.php:17` | `Auth::attempt(credentials)` → redirect by role, **no `terminated_at` check** |
| `database/migrations/2026_08_13_213303_create_activity_logs_table.php:18` `action enum(created,updated,deleted,role_changed,imported,generated,archived,restored)` (`2026_09_13_000002_extend_activity_logs_action_enum.php:1` adds `archived,restored`) |

**Gap:** Every termination is permanent, loses attribution, no reason audit, no reinstate. Burial archiving (`app/Models/BurialRecord.php:14` `archived_at/reason/by/notes`, `scopeActive/Archived`, `BurialRecordService.php:121` `archive()/restore()`) provides ready template.

---

## Target State

### 1. Database — Add Columns to `users` (Not a New Table)

Single migration adds termination state:

```php
$table->timestamp('terminated_at')->nullable()->after('updated_at')->index();
$table->enum('terminated_reason', ['resigned','retired','terminated','end_of_contract','transferred','other'])->nullable()->after('terminated_at');
$table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete()->after('terminated_reason');
$table->text('terminated_notes')->nullable()->after('terminated_by');
```

Lot analog: `users.email` stays reserved (unique) so terminated email cannot be re-invited without reinstate; `burialRecords` stay attributed (FK not nulled). Restoration clears `terminated_*` and allows login again.

`activity_logs.action` enum extended via second migration: add `'terminated','reinstated'` (must redeclare all 8 existing + 2 new via `MODIFY`).

### 2. Hidden by Default + `Terminated` Filter on Index

- Default (`filter=all|clerk|head|admin` or no filter): `WHERE users.terminated_at IS NULL`. Terminated never appear.
- Only when `filter=terminated`: `WHERE users.terminated_at IS NOT NULL`. Search + role + sort compose (`?filter=terminated&search=juan&sort_field=first_name`).
- Index filter dropdown gains radio `Terminated` next to `All|Admin|Head|Clerk`. Badge shows `Terminated` when active. No separate page — same `IndexView.vue`.

### 3. Show — Single Green `Reinstate` Button for Terminated

- **Active:** header actions `Edit` (if `canEdit`) + `Terminate` (red, opens reason modal) — records tab editable.
- **Terminated:** **all normal actions hidden**. Single button visible:

```vue
<button
    v-if="isTerminated"
    @click="openReinstateModal"
    class="flex items-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200"
>
    <!-- rotate-ccw icon -->
    Reinstate
</button>
```

- Amber banner at top of card when terminated: `Terminated on {at} — Reason: {reason} — By {by.full_name}` + `notes` if present.
- Terminated tabs read-only (`editing` forced `false`).

### 4. Terminate Flow (Replaces Delete)

- Clerk clicks `Terminate` (currently `Delete`) → opens `HSOverlay` modal (not `confirm`):
  - `select terminated_reason` required: `resigned | retired | terminated | end_of_contract | transferred | other`
  - `textarea terminated_notes` required only when `other`, optional otherwise
  - `Cancel` / `Terminate` (red) buttons
- `Terminate` posts to `POST /admin/user-management/{user}/terminate` → sets `terminated_at=now()`, `terminated_reason`, `terminated_by=auth()->id()`, `terminated_notes`; logs `terminated`; does **not** null `burialRecords.user_id`.
- Toast: `User terminated successfully.` + redirect to `admin.user_management.index` (burial analog `archived` → index).

### 5. Reinstate Flow

- Terminated `ShowView` green `Reinstate` click → opens `HSOverlay` modal `Restore Burial Record` analog:
  - Title `Reinstate Account`, text “This will make the account active again and allow login.”
  - Details table `Full Name, Email, Role, Terminated At/Reason/By`
  - Warning `If email is already taken by an active user, reinstate will fail. Free email first or use different email.`
  - Footer single green `Reinstate` + X at top for cancel (no bottom Cancel per previous confirmation modal request, ensure proper close).
- `Reinstate` posts to `POST /admin/user-management/{user}/reinstate`:
  - Guards `abort_if !terminated`, email still unique so no lot-contention analog except email collision → `ValidationException` “Email already taken by active user.”
  - On success: nulls `terminated_*`, logs `reinstated`, toast `User reinstated successfully.` → redirect to `index`.

---

## Backend Changes

### 1. Migrations

File: `database/migrations/2026_XX_XX_add_termination_to_users_table.php`

```php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('terminated_at')->nullable()->after('updated_at')->index();
    $table->enum('terminated_reason', ['resigned','retired','terminated','end_of_contract','transferred','other'])->nullable()->after('terminated_at');
    $table->foreignId('terminated_by')->nullable()->constrained('users')->nullOnDelete()->after('terminated_reason');
    $table->text('terminated_notes')->nullable()->after('terminated_by');
});
```

File: `database/migrations/2026_XX_XX_extend_activity_logs_action_enum_terminated.php` — add `terminated`, `reinstated` (`DB::statement("ALTER TABLE activity_logs MODIFY COLUMN action ENUM('created','updated','deleted','role_changed','imported','generated','archived','restored','terminated','reinstated') NOT NULL")`).

### 2. Model — `app/Models/User.php:10`

```php
protected $fillable = [...,'terminated_at','terminated_reason','terminated_by','terminated_notes'];

protected $casts = ['email_verified_at'=>'datetime','password'=>'hashed','terminated_at'=>'datetime'];

public function terminatedBy(): BelongsTo { return $this->belongsTo(User::class,'terminated_by'); }
public function isTerminated(): bool { return $this->terminated_at !== null; }
public function scopeActive($q){ return $q->whereNull('terminated_at'); }
public function scopeTerminated($q){ return $q->whereNotNull('terminated_at'); }
```

No global scope — filtering explicit to avoid surprise in `LoginController`/`AdminMiddleware`.

### 3. Request — `app/Http/Requests/Admin/UserTerminateRequest.php` (new, mirrors `Clerk/BurialRecordArchiveRequest.php:6`)

```php
public function rules(): array {
    return [
        'terminated_reason' => ['required','in:resigned,retired,terminated,end_of_contract,transferred,other'],
        'terminated_notes' => ['nullable','string','max:1000','required_if:terminated_reason,other'],
    ];
}
```

### 4. Service (optional) — `app/Services/UserManagementService.php` (mirrors `BurialRecordService.php:121`)

```php
public function terminate(User $user, array $data, int $by): User {
    abort_if($user->terminated_at !== null, 422, 'Already terminated.');
    return DB::transaction(fn()=>tap($user,fn()=>$user->update([
        'terminated_at'=>now(),'terminated_reason'=>$data['terminated_reason'],
        'terminated_by'=>$by,'terminated_notes'=>$data['terminated_notes']??null,
    ])));
}
public function reinstate(User $user): User {
    abort_if($user->terminated_at===null,422,'Not terminated.');
    if (User::active()->where('email',$user->email)->where('id','!=',$user->id)->exists()){
        throw ValidationException::withMessages(['email'=>'Email already taken by active user. Free email first.']);
    }
    return DB::transaction(fn()=>tap($user,fn()=>$user->update([
        'terminated_at'=>null,'terminated_reason'=>null,'terminated_by'=>null,'terminated_notes'=>null,
    ])));
}
```

### 5. Controller — `app/Http/Controllers/Admin/UserManagementController.php:87`

```php
public function terminate(UserTerminateRequest $req, User $user){
    if($user->id===$req->user()->id) return back()->with('error','You cannot terminate your own account.');
    if($user->role==='admin') return back()->with('error','Admin accounts cannot be terminated.');
    $this->service->terminate($user,$req->validated(),auth()->id());
    $this->logActivity('terminated',$user,"Terminated user {$user->first_name} {$user->last_name}",null,$req->validated());
    return to_route('admin.user_management.index')->with('success','User terminated successfully.');
}
public function reinstate(User $user){
    $this->service->reinstate($user);
    $this->logActivity('reinstated',$user,"Reinstated user {$user->first_name} {$user->last_name}");
    return to_route('admin.user_management.index')->with('success','User reinstated successfully.');
}
// keep destroy() deprecated 1 release
```

Update `applyFilters()` to first predicate:
```php
->when($filter==='terminated', fn($q)=>$q->whereNotNull('users.terminated_at'),
                               fn($q)=>$q->whereNull('users.terminated_at'))
```

Update `show()` to use `Active` scope for `BurialRecordService` already filters `archived`, and load `terminatedBy`.

### 6. Auth Block — `app/Http/Controllers/Auth/LoginController.php:17` + `app/Http/Middleware/AdminMiddleware.php:11`/`ClerkMiddleware.php`

After `Auth::attempt` check `if(Auth::user()->terminated_at){ Auth::logout(); return back()->withErrors(['email'=>'Account terminated. Contact admin.']); }`
Middleware add `|| auth()->user()->terminated_at` → `abort(403,'Account terminated.')`.

### 7. Resources — `app/Http/Resources/UserResource.php` (new) or inline `ShowView` `user_data`

```php
'is_terminated' => (bool)$this->terminated_at,
'terminated' => $this->when($this->terminated_at, fn()=>[
    'at'=>$this->terminated_at?->toISOString(),
    'reason'=>$this->terminated_reason,
    'notes'=>$this->terminated_notes,
    'by'=> $this->whenLoaded('terminatedBy', fn()=> $this->terminatedBy ? new UserResource($this->terminatedBy):null),
]),
```

Frontend accesses via `props.user_data.is_terminated` + `props.user_data.terminated.{at,reason,notes,by.full_name}` (mirrors `props.burial_record.data.is_archived` `ShowView.vue:37`).

### 8. Routes — `routes/admin.php:41`

```php
Route::post('/user-management/{user}/terminate','terminate')->name('user_management.terminate');
Route::post('/user-management/{user}/reinstate','reinstate')->name('user_management.reinstate');
// keep DELETE destroy deprecated
```

---

## Frontend Changes

### `resources/js/Pages/Admin/UserManagement/IndexView.vue:239`

**Filter dropdown** — add `Terminated` radio after `Clerk`:

```vue
<label for="filter-terminated" class="flex items-center py-2.5 px-3 cursor-pointer hover:bg-gray-50 dark:hover:bg-neutral-800">
    <input type="radio" name="filter" value="terminated" id="filter-terminated"
        :checked="filters.filter==='terminated'" @change="applyFilter('terminated')" />
    <span class="ms-3 text-sm text-gray-800 dark:text-neutral-200">Terminated</span>
</label>
```

**Badge label** — extend ternary:

```js
filters.filter==='terminated'?'Terminated':filters.filter==='admin'?'Admin':filters.filter==='head'?'Head':filters.filter==='clerk'?'Clerk':'All'
```

**Table row** — when `user.is_terminated` show amber `Terminated` pill instead of role, `opacity-60`:

```vue
<tr :class="['bg-white ...', user.is_terminated?'opacity-60':'']">
  <span v-if="user.is_terminated" class="bg-amber-100 text-amber-800 dark:bg-amber-800/30">Terminated</span>
</tr>
```

**Empty state** — `No terminated users found` when `filter==='terminated'`.

### `resources/js/Pages/Admin/UserManagement/ShowView.vue:110`

**State:**

```js
const isTerminated = computed(()=>!!props.user_data.is_terminated);
const terminateForm = ref({terminated_reason:"",terminated_notes:""});
const terminateClientErrors = ref({terminated_reason:"",terminated_notes:""});
const terminateServerErrors = ref({terminated_reason:"",terminated_notes:""});
watch(isTerminated,val=>{if(val)editing.value=false});
```

**Header actions** `ShowView.vue:140` `canDelete/canEdit`:

```vue
<template v-if="isTerminated">
    <button @click="openReinstateModal"
        class="flex items-center gap-x-2 px-4 py-2 rounded-xl border border-transparent bg-green-500/10 text-green-600 dark:text-green-400 hover:bg-green-500/20 hover:border-green-500/40 hover:text-green-700 dark:hover:text-green-300 transition-all duration-200">
        <svg>rotate-ccw</svg> Reinstate
    </button>
</template>
<template v-else>
    <button v-if="canEdit" @click="editing=!editing">Edit</button>
    <button v-if="canDelete" @click="openTerminateModal" class="bg-red-500/10 text-red-500 ...">Terminate</button>
</template>
```

Guard `editing` — `watch(isTerminated)`.

**Amber banner** — above card container, only when `isTerminated`:

```vue
<div v-if="isTerminated" class="mb-6 flex flex-col gap-2 rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/20 px-4 py-3">
    <div class="flex items-center gap-2 text-amber-700 dark:text-amber-400"><svg>warning</svg><span class="text-sm font-semibold">Terminated Account</span></div>
    <p class="text-sm text-amber-800 dark:text-amber-300">
        <span v-if="user_data.terminated?.at">Terminated on {{ new Date(terminated.at).toLocaleString() }}</span>
        <span v-if="terminated?.reason"> — Reason: {{ mapReason(terminated.reason) }}</span>
        <span v-if="terminated?.by?.full_name"> — By {{ terminated.by.full_name }}</span>
    </p>
    <p v-if="terminated?.notes" class="text-sm bg-white/60 dark:bg-neutral-800/50 rounded-lg px-3 py-2">{{ terminated.notes }}</p>
</div>
```

**Terminate modal** — new `HSOverlay` `#terminate-modal` (mirror `#archive-modal` `ShowView.vue:1200`):

```vue
<select v-model="terminateForm.terminated_reason" @change="terminateClientErrors.terminated_reason=''">
    <option value="">Select reason</option>
    <option value="resigned">Resigned</option>
    <option value="retired">Retired</option>
    <option value="terminated">Terminated</option>
    <option value="end_of_contract">End of Contract</option>
    <option value="transferred">Transferred</option>
    <option value="other">Other</option>
</select>
<textarea v-model="terminateForm.terminated_notes" @input="terminateClientErrors.terminated_notes=''" :required="terminated_reason==='other'" placeholder="Details (required if Other)" />
```

```js
const openTerminateModal = ()=>{ terminateClientErrors.value={terminated_reason:"",terminated_notes:""}; terminateServerErrors.value={terminated_reason:"",terminated_notes:""}; HSOverlay.open("#terminate-modal"); };
const closeTerminateModal = ()=>{ HSOverlay.close("#terminate-modal"); terminateForm.value={terminated_reason:"",terminated_notes:""}; };
const terminateUser = ()=>{
    terminateClientErrors.value={terminated_reason:"",terminated_notes:""};
    let hasError=false;
    if(!terminateForm.value.terminated_reason){terminateClientErrors.value.terminated_reason="Please select a termination reason."; $toast.error(...); hasError=true;}
    if(terminateForm.value.terminated_reason==="other" && !terminateForm.value.terminated_notes?.trim()){terminateClientErrors.value.terminated_notes="Please provide details for 'Other' reason."; hasError=true;}
    if(hasError) return;
    router.post(route('admin.user_management.terminate', props.user_data.id),
        {terminated_reason: terminateForm.value.terminated_reason, terminated_notes: terminateForm.value.terminated_notes},
        { preserveScroll:true, onSuccess:()=>{ $toast.success("User terminated successfully!"); HSOverlay.close("#terminate-modal"); }, onError:(err)=>{ terminateServerErrors.value={terminated_reason:err.terminated_reason||"",terminated_notes:err.terminated_notes||""}; $toast.error(err.terminated_reason||err.terminated_notes||"Failed to terminate user."); }});
};
```

**Reinstate modal** — `#reinstate-modal` green (`bg-green-500/10`) with `X` at top for cancel (no bottom Cancel per previous plan), details table `Full Name, Email, Role, Terminated At/Reason/By`, warning “Reinstating will make account active again and allow login. Cancel (X) to abort.” Footer single green `Reinstate` (`bg-green-500/10`) → `router.post(route('admin.user_management.reinstate', id), {}, {preserveScroll:true, onSuccess:toast+close})`; `closeReinstateModal` robust `closeArchiveOverlay` pattern removes `.hs-overlay-backdrop` + `overflow-hidden`.

**Cleanup** — `onMounted/onBeforeUnmount` `cleanupAllOverlays()` closes all `.hs-overlay` + backdrop (fixes `IndexView.vue:161` dark overlay bug, same as burial).

---

## Files to Modify

| File | Change |
|------|--------|
| `database/migrations/2026_XX_XX_add_termination_to_users_table.php` | **Create** `terminated_at` indexed, `terminated_reason` enum, `terminated_by` FK `users` nullOnDelete, `terminated_notes` |
| `database/migrations/2026_XX_XX_extend_activity_logs_action_enum_terminated.php` | **Create** `terminated,reinstated` (`MODIFY` redeclare all 10) |
| `app/Models/User.php:10` | Fillable/casts/relation `terminatedBy`/ `isTerminated`/`scopeActive/Terminated` |
| `app/Http/Requests/Admin/UserTerminateRequest.php` | **Create** validation `required_if:other`, `max:1000` |
| `app/Services/UserManagementService.php` or `UserManagementController.php` | `terminate()/reinstate()` `DB::transaction` + `abort_if` + email collision check |
| `app/Http/Controllers/Admin/UserManagementController.php:87` | `terminate/reinstate`, `applyFilters` terminated branch, `logActivity('terminated'/'reinstated')`, keep `destroy` deprecated |
| `app/Http/Controllers/Auth/LoginController.php:17` | Block `terminated_at` |
| `app/Http/Middleware/AdminMiddleware.php:11` + `ClerkMiddleware.php` | Block terminated (`|| terminated_at`) |
| `app/Http/Resources/UserResource.php` (**Create**)` | `is_terminated` + `terminated{at,reason,notes,by}` |
| `routes/admin.php:41` | `POST /terminate` `POST /reinstate` |
| `resources/js/Pages/Admin/UserManagement/IndexView.vue:239` | `Terminated` filter radio + badge + row `opacity-60` + amber pill |
| `resources/js/Pages/Admin/UserManagement/ShowView.vue:110` | `isTerminated`, amber banner, green `Reinstate` vs `Terminate`, two `HSOverlay` modals with client+server errors at bottom + toast add-on, `cleanupAllOverlays` |
| `tests/Feature/AdminUserManagementTest.php:10` `beforeEach` | `if (!Schema::hasColumn('users','terminated_at')) Schema::table(... string ...)` for `sqlite` |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `app/Models/BurialRecord.php:14` | Burial archiving stays, no `User` coupling change beyond `terminatedBy` FK |
| `app/Repositories/BurialRecordRepository.php:23` | Already filters `archived`, no `User` terminated logic |
| `resources/js/Pages/Shared/BurialRecords/ShowView.vue:1200` | Archive/restore modals unchanged, pattern reused |
| `database/migrations/2026_09_13_000001_add_archiving...` | Existing, not touched |

---

## Implementation Order

1. Migration: `users` terminated cols + index + FK
2. Migration: `activity_logs.action` enum extend (`terminated,reinstated`)
3. Model: `User` fillable/casts/relations/scopes
4. Request: `UserTerminateRequest`
5. Service: `terminate()/reinstate()` with email collision guard
6. Controller: `terminate()/reinstate()` + `applyFilters` + `LogsActivity` + `destroy` deprecate
7. Auth: `LoginController` + `Admin/ClerkMiddleware` block terminated
8. Resource: `is_terminated`/`terminated`
9. Routes: `admin.php` terminate/reinstate
10. Frontend Index: `Terminated` filter
11. Frontend Show: terminate modal + green Reinstate modal + banner + guards
12. Tests `beforeEach` sqlite column + `vendor/bin/pint --dirty`, `npx prettier --write`, `npm run build`, manual filter + login-block + lot-analog email collision

---

## Verification

* `GET /admin/user-management` hides terminated; `?filter=terminated` only terminated; search `?search=juan&filter=terminated` composes; role filter `all|clerk|head|admin` default hides terminated
* Active Show: `Edit` (if `canEdit`) + `Terminate` (red) vs terminated: **only** `Reinstate` green `bg-green-500/10` + X cancel, no `Edit/Delete`, banner with `terminated.at/reason/by/notes`
* Terminate modal requires `terminated_reason`, `notes required_if other`, inline error at bottom + toast add-on, properly closes (no backdrop dark on `IndexView.vue:161`)
* Reinstate modal X cancels, `Reinstate` posts → `archived lot` analog no lot contention; if email taken by active user → `422 email` error at bottom + toast, stays terminated
* Terminated `clerk` cannot `Auth::attempt` (“Account terminated.”) nor pass `admin/clerk` middleware (403)
* `burial_records.user_id` stays (not nulled) — admin still sees who created burial via `BurialRecordResource` `imported_by`; `activity_logs` has `terminated`/`reinstated` rows with `subject_type=User`
* `npm run build` + `php artisan test --compact` 62+ passes (sqlite manual column)

---

## Risk Notes

* **Email uniqueness:** `users.email unique` still reserves terminated email; new invite with same email will `unique` fail — must reinstate, not re-invite. If reuse required, scope invite `unique:users,email,NULL,id,terminated_at,NULL` or free email on terminate (lose audit). Document.
* **Login vs middleware:** must block both `LoginController` *and* `Admin/ClerkMiddleware` — else terminated admin could `attempt` then 403 on first admin route with confusing flow.
* **Enum `MODIFY`:** must redeclare all 10 values; sqlite tests need `string` fallback (enum → text) or manual `Schema::table` string columns.
* **Keep `destroy()`:** 1 release as deprecated or `410` — existing `HSOverlay` `delete-user-modal` callers will 405 if removed immediately; close properly via `cleanupAllOverlays`.
* **Self/admin guards:** retain `cannot terminate self` + `cannot terminate admin` as `destroy` does; `head` termination allowed?

---

## Open Questions

1. **Reason enum** — is `resigned,retired,terminated,end_of_contract,transferred,other` correct? Need `resigned_with_notice`, `AWOL`, `deceased`?
2. **Filter label** — `Terminated` vs `Deactivated` vs `Archived` (keep burial term consistent)?
3. **Admin self-protect** — keep `cannot terminate self` + `cannot terminate admin`?
4. **Email collision** — reinstate when email taken → “Email already taken by active user. Free email first.” Acceptable?
5. **Login message** — “Account terminated. Contact admin.” vs 403?
6. **Keep hard delete?** — `DELETE /{user}` forever or alias to `terminate` for 1 release?
