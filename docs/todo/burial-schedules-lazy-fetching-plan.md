# Burial Schedules — Lazy Data Fetching & Reduced UI Bloat

## Problem

The `Clerk/BurialSchedules/IndexView.vue` calendar page suffers from:

1. **All burial records loaded at once** — `BurialScheduleController::index()` calls `->get()` with no date filtering, potentially fetching hundreds/thousands of records
2. **Static events prop** — FullCalendar receives all events as a single prop on initial load. Navigating months/years shows stale pre-loaded data instead of fetching the relevant range
3. **No loading state** — the page is blank until the full dataset arrives
4. **Blocking Inertia render** — the entire page waits on the DB query before rendering anything

---

## Recommended Approach: FullCalendar `events` function + Laravel API Endpoint

Replace the static `events: props.burialSchedules` with a callback that fetches only the visible date range from the server. This is the standard pattern for calendar applications.

---

### 1. API Controller — `BurialScheduleApiController`

**File:** `app/Http/Controllers/Api/BurialScheduleApiController.php`
**Namespace:** `App\Http\Controllers\Api`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BurialRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BurialScheduleApiController extends Controller
{
    public function index(Request $request)
    {
        $start = $request->query('start');
        $end   = $request->query('end');

        return BurialRecord::with([
                'deceasedRecord:id,first_name,middle_name,last_name,date_of_depository',
                'lot:id,column,row',
            ])
            ->whereHas('deceasedRecord', fn ($q) => $q->whereNotNull('date_of_depository'))
            ->whereHas('deceasedRecord', fn ($q) => $q->whereBetween('date_of_depository', [$start, $end]))
            ->get()
            ->map(fn ($record) => [
                'id'    => $record->id,
                'title' => Str::limit(
                    $record->deceasedRecord->first_name . ' ' . $record->deceasedRecord->last_name,
                    20,
                ),
                'start' => $record->deceasedRecord->date_of_depository,
                'extendedProps' => [
                    'deceased_name' => trim(
                        $record->deceasedRecord->first_name . ' '
                        . ($record->deceasedRecord->middle_name ? $record->deceasedRecord->middle_name . ' ' : '')
                        . $record->deceasedRecord->last_name,
                    ),
                    'lot_info' => $record->lot
                        ? $record->lot->column . '-' . $record->lot->row
                        : 'Unassigned',
                ],
            ]);
    }
}
```

---

### 2. Route

**Option A — Inside `routes/clerk.php`** (under existing `auth` + `clerk` middleware):

```php
use App\Http\Controllers\Api\BurialScheduleApiController;

Route::get('/burial-schedules/events', [BurialScheduleApiController::class, 'index'])
    ->name('api.burial_schedules.index');
```

**Option B — Inside `routes/api.php`** (stateless, no session):

```php
use App\Http\Controllers\Api\BurialScheduleApiController;

Route::middleware('auth:sanctum')->get('/burial-schedules', [BurialScheduleApiController::class, 'index'])
    ->name('api.burial_schedules.index');
```

> Option A is recommended since the app already uses session-based auth and the clerk middleware is already applied.

---

### 3. Simplify `BurialScheduleController`

**File:** `app/Http/Controllers/Clerk/BurialScheduleController.php`

Remove the `burialSchedules` query and prop entirely — the calendar fetches its own data via the API endpoint:

```php
<?php

namespace App\Http\Controllers\Clerk;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class BurialScheduleController extends Controller
{
    public function index()
    {
        return Inertia::render('Clerk/BurialSchedules/IndexView');
    }
}
```

---

### 4. Frontend — `resources/js/Pages/Clerk/BurialSchedules/IndexView.vue`

Remove the `burialSchedules` prop and replace `events` with an async function:

```vue
<script setup>
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Dashboard from "@/Layouts/Dashboard.vue";
import FullCalendar from "@fullcalendar/vue3";
import dayGridPlugin from "@fullcalendar/daygrid";
import interactionPlugin from "@fullcalendar/interaction";

const loading = ref(true);

const calendarOptions = ref({
    plugins: [dayGridPlugin, interactionPlugin],
    initialView: "dayGridMonth",
    headerToolbar: {
        left: "prev,next",
        center: "title",
        right: "dayGridDay,dayGridMonth,dayGridYear",
    },
    views: {
        dayGridYear: {
            type: "dayGrid",
            duration: { years: 1 },
            buttonText: "Yearly",
        },
        dayGridMonth: { buttonText: "Monthly" },
        dayGridDay: {
            type: "dayGrid",
            duration: { days: 1 },
            buttonText: "Today",
        },
    },
    events: async (info, successCallback, failureCallback) => {
        try {
            loading.value = true;
            const params = new URLSearchParams({
                start: info.startStr,
                end: info.endStr,
            });
            const response = await fetch(
                route("clerk.api.burial_schedules.index") + `?${params}`,
            );
            const data = await response.json();
            successCallback(data);
        } catch (error) {
            failureCallback(error);
        } finally {
            loading.value = false;
        }
    },
    eventClick: (info) => {
        router.visit(route("clerk.burial_records.show", info.event.id));
    },
    height: "auto",
    eventDisplay: "block",
    eventBackgroundColor: "transparent",
    eventBorderColor: "#16a34a",
    eventTextColor: "#16a34a",
});

defineOptions({ layout: Dashboard });
</script>
```

Add a loading skeleton inside the template:

```vue
<template>
    <div class="max-w-340 px-4 py-10 sm:px-6 lg:px-8 lg:py-6 mx-auto">
        <div class="flex flex-col">
            <div class="-m-1.5 overflow-x-auto">
                <div class="p-1.5 min-w-full inline-block align-middle">
                    <div
                        class="bg-white dark:bg-neutral-800 border border-gray-200 dark:border-neutral-700 rounded-xl shadow-2xs overflow-hidden"
                    >
                        <div class="p-6 overflow-x-auto">
                            <!-- Loading skeleton -->
                            <div
                                v-if="loading"
                                class="space-y-3 min-w-200"
                            >
                                <div class="h-8 bg-gray-200 dark:bg-neutral-700 rounded animate-pulse w-48" />
                                <div class="grid grid-cols-7 gap-1">
                                    <div
                                        v-for="n in 35"
                                        :key="n"
                                        class="h-20 bg-gray-100 dark:bg-neutral-800 rounded animate-pulse"
                                    />
                                </div>
                            </div>

                            <div
                                v-show="!loading"
                                class="min-w-200"
                            >
                                <FullCalendar :options="calendarOptions" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
```

---

### 5. Summary of Changes

| Action | File | Description |
|--------|------|-------------|
| Create | `app/Http/Controllers/Api/BurialScheduleApiController.php` | API endpoint with date-range filtering |
| Edit   | `routes/clerk.php` | Add `GET /burial-schedules/events` route |
| Edit   | `app/Http/Controllers/Clerk/BurialScheduleController.php` | Remove `burialSchedules` query and prop |
| Edit   | `resources/js/Pages/Clerk/BurialSchedules/IndexView.vue` | Replace static `events` with async fetch + loading skeleton |

---

### 6. Why This Approach Over Alternatives

| Approach | Problem |
|----------|---------|
| **Current** (static prop) | All records loaded at once, no range filtering, stale on navigation |
| **`Inertia::defer()`** | Only defers render — still loads ALL records in the background |
| **`Inertia::defer()` + pagination** | Complex pagination logic, calendar can't paginate arbitrarily |
| **FullCalendar `events` fetch** (recommended) | Only fetches visible range, calendar renders immediately, data refreshes on navigation |

---

### 7. Verification

- Load the page — calendar should appear immediately with a skeleton, then populate
- Click "Next" / "Prev" — only the new month's events should be fetched (check Network tab)
- Switch between Day/Month/Year views — data should fetch for each view's range
- Click an event — should navigate to `burial_records.show`
- Run `vendor/bin/pint --dirty --format agent` on new PHP files
