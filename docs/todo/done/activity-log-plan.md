# Admin Activity Log — Phase 3

## Overview
Add a **lightweight custom activity log** for user management actions, with a standalone visual log viewer page at `/admin/activity-log`. Tracks who did what, when, and with what changes. Provides stat cards (today's actions, most active user, total logged actions), charts (actions/day bar chart, action breakdown doughnut chart), and a filterable/searchable log table. No external dependencies — custom `activity_logs` table and `ActivityLog` model.

---

## Current State (After Phase 2)

| Area | Status |
|------|--------|
| `UserManagementController` | `index()`, `show()`, `update()`, `export()`, `destroy()` with guards |
| User tracking | `burial_records.user_id` tracks record creation; `imported_logs` tracks file imports |
| Audit trail | ❌ No log of who updated which user, role changes, profile edits |
| Log viewing | ❌ No dedicated log page; only raw `storage/logs/laravel.log` via `Log::` facade |
| ImportedLog | Exists but only tracks file import operations (file_name, status) |

**Context:** Admins can now edit user profiles, change roles, and delete users via ShowView. There is no record of these changes. A lightweight activity log adds accountability and a visual overview of admin actions without the overhead of Spatie's activitylog package.

---

## Target State (After)

### Table: `activity_logs`

```
activity_logs
├── id                  (bigint, PK, auto-increment)
├── user_id             (bigint, FK → users.id, nullable, nullOnDelete — who performed the action)
├── action              (enum: 'created', 'updated', 'deleted', 'role_changed', 'imported')
├── subject_type        (string — 'User', 'ImportedLog')
├── subject_id          (bigint — ID of the affected record)
├── description         (text — human-readable: "Updated John Doe's profile")
├── properties          (JSON — old/new values diff, nullable)
├── ip_address          (string, nullable)
├── created_at          (timestamp)
└── updated_at          (timestamp)
```

Indexes: `['user_id', 'created_at']`, `['action', 'created_at']`, `['subject_type', 'subject_id']`

### Page: `/admin/activity-log`

**Layout (mirrors DashboardView):**
- Header: "Activity Log" title
- Filter bar: Date range (today / 7 days / 30 days / all), Action type dropdown (All / Created / Updated / Deleted / Role Changed / Imported), User search
- Stat cards row (reuses `StatCard.vue`):
  - Total Actions (today)
  - Most Active User (name + count)
  - Total Logged Actions (all time)
- Charts row (reuses existing `BarChart.vue` + `DoughnutChart.vue`):
  - **Actions per Day** — BarChart, last 30 days, x-axis = date, y-axis = count
  - **Action Breakdown** — DoughnutChart, grouped by action type (created, updated, deleted, etc.)
- Log table (reuses `TableHeader` + `TableData`):
  - Columns: Timestamp, User, Action, Description, Details
  - Each row expandable or with a "View" link → shows old/new values diff
  - Sorted by most recent first
  - Paginated (15 per page)
  - Empty state: "No activity logs found"

### Sidebar Entry

Add an "Activity Log" link under the "Main" section in `Sidebar.vue`, admin-only (`v-if="user.role === 'admin'"`), after "User Management". Uses a clock/history icon.

---

## Backend Changes

### 1. Migration: `create_activity_logs_table`

```php
Schema::create('activity_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignIdFor(User::class, 'user_id')->nullable()->constrained()->nullOnDelete();
    $table->enum('action', ['created', 'updated', 'deleted', 'role_changed', 'imported']);
    $table->string('subject_type');
    $table->unsignedBigInteger('subject_id');
    $table->text('description');
    $table->json('properties')->nullable();
    $table->string('ip_address', 45)->nullable();
    $table->timestamps();

    $table->index(['user_id', 'created_at']);
    $table->index(['action', 'created_at']);
    $table->index(['subject_type', 'subject_id']);
});
```

### 2. Model: `ActivityLog`

```php
// app/Models/ActivityLog.php
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id', 'action', 'subject_type', 'subject_id',
        'description', 'properties', 'ip_address',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

### 3. Trait: `LogsActivity`

A simple trait that controllers can use to log actions:

```php
// app/Traits/LogsActivity.php
trait LogsActivity
{
    protected function logActivity(
        string $action,
        Model $subject,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
    ): ActivityLog {
        $properties = null;
        if ($oldValues || $newValues) {
            $properties = array_filter([
                'old' => $oldValues,
                'new' => $newValues,
            ]);
        }

        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'description' => $description,
            'properties' => $properties,
            'ip_address' => request()->ip(),
        ]);
    }
}
```

### 4. Logging in `UserManagementController`

Add `use LogsActivity;` trait. Log actions in:

- **`update()`**: After successful update, log `'updated'` with old/new diff. Special case: if role changed, log `'role_changed'` instead with description "Changed {name}'s role from {old} to {new}".
- **`destroy()`**: Before delete, log `'deleted'` with description "Deleted user {name}".

### 5. Logging in `ClerkInvitationController`

- **`store()`**: After invite sent, log `'created'` with description "Invited {email} as clerk".

### 6. `ActivityLogController`

```php
// app/Http/Controllers/Admin/ActivityLogController.php
class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        // Filter: date range
        if ($request->filled('range') && $request->range !== 'all') {
            $days = match ($request->range) {
                'today' => 0,
                '7days' => 7,
                '30days' => 30,
                default => null,
            };
            if ($days !== null) {
                $query->where('created_at', '>=', now()->subDays($days));
            }
        }

        // Filter: action type
        if ($request->filled('action') && $request->action !== 'all') {
            $query->where('action', $request->action);
        }

        // Filter: user search
        if ($request->filled('user_search')) {
            $search = $request->user_search;
            $query->whereHas('user', fn ($q) => $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%"));
        }

        $logs = $query->paginate(15)->withQueryString();

        // Stats
        $todayCount = ActivityLog::whereDate('created_at', today())->count();
        $totalCount = ActivityLog::count();
        $mostActiveUser = ActivityLog::whereNotNull('user_id')
            ->selectRaw('user_id, count(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->with('user:id,first_name,last_name')
            ->first();

        // Chart data: actions per day (last 30 days)
        $actionsPerDay = ActivityLog::where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, count(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Chart data: action breakdown
        $actionBreakdown = ActivityLog::selectRaw('action, count(*) as count')
            ->groupBy('action')
            ->get();

        return Inertia::render('Admin/ActivityLog/IndexView', [
            'logs' => $logs,
            'filters' => [
                'range' => $request->range,
                'action' => $request->action,
                'user_search' => $request->user_search,
            ],
            'stats' => [
                'today_count' => $todayCount,
                'total_count' => $totalCount,
                'most_active_user' => $mostActiveUser,
            ],
            'chart_data' => [
                'actions_per_day' => $actionsPerDay,
                'action_breakdown' => $actionBreakdown,
            ],
        ]);
    }
}
```

### 7. Routes (`admin.php`)

```php
Route::get('/activity-log', [ActivityLogController::class, 'index'])->name('activity_log.index');
```

---

## Frontend: IndexView.vue

### Script section

| Pattern | Source reference |
|---------|-----------------|
| Props | `logs` (paginated), `filters`, `stats`, `chart_data` |
| Reused components | `StatCard`, `BarChart`, `DoughnutChart`, `TableHeader`, `TableData`, `Dashboard` layout |
| Filter refs | `range`, `action`, `userSearch` — synced to URL via `router.get` with `preserveState` |
| Chart data transformation | Convert `actions_per_day` to Chart.js format `{ labels: [...], datasets: [{ data: [...] }] }` |
| Color mapping for actions | `created` → green, `updated` → blue, `deleted` → red, `role_changed` → amber, `imported` → purple |

### Template structure

```
<template>
  <div class="max-w-7xl mx-auto p-6">
    <!-- Header: title -->

    <!-- Filter bar: range buttons, action dropdown, user search -->

    <!-- Stat cards row: StatCard × 3 -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
      <StatCard title="Actions Today" :value="stats.today_count" />
      <StatCard title="Most Active User" :value="mostActiveUserName" />
      <StatCard title="Total Actions" :value="stats.total_count" />
    </div>

    <!-- Charts row -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
      <BarChart :chartData="..." :chartOptions="..." />
      <DoughnutChart :chartData="..." :chartOptions="..." />
    </div>

    <!-- Log table -->
    <table> ... </table>

    <!-- Pagination -->
  </div>
</template>
```

### Log table row

Each row shows:
- **Timestamp**: formatted `Oct 15, 2026 3:42 PM`
- **User**: full name (linked to show page) or "System" if `user_id` is null
- **Action**: colored badge (green/blue/amber/red/purple)
- **Description**: human-readable text
- **Details**: expandable row or "View" button → shows old/new values in a mini diff view (using `<details>` element or HSOverlay modal)

---

## Files to Modify

| File | Change |
|------|--------|
| `database/migrations/XXXX_create_activity_logs_table.php` | **New** — migration |
| `app/Models/ActivityLog.php` | **New** — model |
| `app/Traits/LogsActivity.php` | **New** — reusable logging trait |
| `app/Http/Controllers/Admin/ActivityLogController.php` | **New** — `index()` with filters, stats, chart data |
| `app/Http/Controllers/Admin/UserManagementController.php` | Add `use LogsActivity;` + log calls in `update()`, `destroy()` |
| `app/Http/Controllers/Admin/ClerkInvitationController.php` | Add `use LogsActivity;` + log call in `store()` |
| `routes/admin.php` | +1 GET route (`activity_log.index`) |
| `resources/js/Components/Dashboard/Sidebar.vue` | Add "Activity Log" sidebar link (admin-only) |
| `resources/js/Pages/Admin/ActivityLog/IndexView.vue` | **New** — log viewer page |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `User` model | No schema changes |
| `users` migration | No schema changes |
| `ImportedLog` model | Untouched; separate concern |
| `DashboardView` | No changes needed |
| `StatCard.vue`, `BarChart.vue`, `DoughnutChart.vue` | Reused as-is |

---

## Out of Scope
- **Activity log for burial records** — only user management actions tracked in this phase; can add `BurialRecord` logging later by applying the same `LogsActivity` trait
- **Log export** — CSV/PDF export of activity logs; can be added later
- **Log pruning/archiving** — no automatic cleanup; admin can manually truncate if needed
- **Real-time updates** — polling/websockets for live log updates; not needed for this use case
- **IP geolocation** — storing IP is sufficient; geolocation is a separate concern

---

## Implementation Order

1. Create migration `create_activity_logs_table`
2. Create `ActivityLog` model
3. Create `LogsActivity` trait
4. Add logging calls to `UserManagementController` (`update`, `destroy`)
5. Add logging call to `ClerkInvitationController` (`store`)
6. Create `ActivityLogController` with `index()` method
7. Add route to `admin.php`
8. Add sidebar link to `Sidebar.vue`
9. Create `ActivityLog/IndexView.vue` — stat cards, charts, filterable table
10. Verify: `vendor/bin/pint`, `npx prettier --write`, `npm run build`

---

## Risk Notes

- **Historical data gap**: Existing user management actions (before this feature) will not have logs. Only actions performed after deployment will be tracked.
- **Performance**: The `activity_logs` table will grow over time. Indexes on `created_at` and `action` ensure query performance. If volume becomes an issue, add a monthly pruning artisan command later.
- **Auth guard**: All logging uses `auth()->id()`. If an action is triggered via artisan or queue, `user_id` will be null — this is intentional for system-level actions.
- **Subject soft deletes**: If a user is deleted, `user_id` is null (via `constrained()->nullOnDelete()`), but the log entry and description remain intact for audit purposes.
