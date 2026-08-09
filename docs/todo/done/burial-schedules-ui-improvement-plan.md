# Burial Schedules — Calendar UI Improvement: Day Cell Overflow & Date Detail Page

## Overview
Limit events per day cell to 5, with a "view more..." link navigating to a dedicated page showing all burials for that date. Individual event clicks still go to burial record ShowView.

**Dependency:** Builds on `burial-schedules-lazy-fetching-plan.md` (async events + API endpoint).

---

## Current State

| Area | Status |
|------|--------|
| Calendar | Loads ALL records at once, no day cell limit |
| Day cell overflow | No `dayMaxEvents` configured |
| Event click | Navigates to burial record ShowView |
| Date detail page | ❌ No page to view all burials for a specific date |
| Loading state | ❌ No skeleton or spinner while calendar data loads |

**Context:** When many burial records share the same date (common during peak periods), the day cell becomes overloaded with stacked events. The user needs a way to quickly see all burials for a given day without scrolling through a cluttered calendar cell.

---

## Target State

### Calendar Day Cell (IndexView)
- Maximum **5 events** visible per day cell
- A **"+N more"** link appears below the 5th event when there are additional burials
- Clicking "+N more" navigates to `/clerk/burial-schedules/date/{date}` — the date detail page
- Individual event clicks continue to navigate to `clerk.burial_records.show` (unchanged)
- A **loading skeleton** is shown while calendar data is being fetched

### Date Detail Page (`/clerk/burial-schedules/date/{date}`)
- **Back button** → `clerk.burial_schedules.index`
- **Header**: calendar icon + formatted date (e.g., "October 15, 2026") + subtitle "Scheduled Burials"
- **Summary card**: "X burial(s) scheduled for this date"
- **Table** with minimal columns:
  | Column | Content |
  |--------|---------|
  | ID | `burial_record.id` |
  | Full Name | Deceased full name (first + middle + last) |
  | Lot | Lot column-row or "Unassigned" |
  | Time | `time_of_depository` formatted (e.g., "8:00 AM") or "Not set" |
- Each row is **clickable** → navigates to `clerk.burial_records.show`
- **Empty state**: "No burials scheduled for this date" with back link

---

## Backend Changes

### 1. API Endpoint (from lazy fetching plan)

Already proposed in `burial-schedules-lazy-fetching-plan.md` — create `BurialScheduleApiController` with date-range filtering. This serves the calendar's async events callback.

### 2. Date Detail: `showByDate()` in `BurialScheduleController`

```php
public function showByDate(string $date)
{
    // Validate date format
    if (!\Carbon\Carbon::hasFormat($date, 'Y-m-d')) {
        abort(404);
    }

    $burials = BurialRecord::with([
            'deceasedRecord:id,first_name,middle_name,last_name,time_of_depository',
            'lot:id,column,row',
        ])
        ->whereHas('deceasedRecord', function ($query) use ($date) {
            $query->where('date_of_depository', $date);
        })
        ->get()
        ->map(fn ($record) => [
            'id' => $record->id,
            'full_name' => trim(
                $record->deceasedRecord->first_name . ' '
                . ($record->deceasedRecord->middle_name ? $record->deceasedRecord->middle_name . ' ' : '')
                . $record->deceasedRecord->last_name,
            ),
            'lot' => $record->lot
                ? $record->lot->column . '-' . $record->lot->row
                : 'Unassigned',
            'time' => $record->deceasedRecord->time_of_depository
                ? \Carbon\Carbon::parse($record->deceasedRecord->time_of_depository)->format('g:i A')
                : 'Not set',
        ]);

    return Inertia::render('Clerk/BurialSchedules/DateView', [
        'burials' => $burials,
        'date' => $date,
    ]);
}
```

### 3. Routes (`clerk.php`)

```php
Route::controller(BurialScheduleController::class)->group(function () {
    Route::get('/burial-schedules', 'index')->name('burial_schedules.index');
    Route::get('/burial-schedules/date/{date}', 'showByDate')->name('burial_schedules.date');
});
```

**Route note:** `{date}` parameter format is `Y-m-d` (e.g., `2026-10-15`). Laravel will capture it as a string.

---

## Frontend Changes

### 1. IndexView.vue — Calendar options update

Add these to the `calendarOptions`:

```js
const calendarOptions = ref({
    // ... existing options ...

    // Limit events per day cell
    dayMaxEvents: 5,

    // Custom text for the "+N more" link
    moreLinkText: (num) => `+${num} more`,

    // Navigate to date detail page on "more" click
    moreLinkClick: (info) => {
        const date = info.dateStr; // e.g., "2026-10-15"
        router.visit(route("clerk.burial_schedules.date", { date }));
    },

    // Loading state for async events
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
});
```

### 2. DateView.vue — New page

**File:** `resources/js/Pages/Clerk/BurialSchedules/DateView.vue`

Layout follows the existing ShowView pattern:

```
<template>
  <div class="max-w-6xl mx-auto p-6">
    <!-- Back button -->
    <button @click="back" class="...">← Back to Calendar</button>

    <!-- Header -->
    <div class="mb-6 flex gap-x-3">
      <div class="flex items-center justify-center size-13 rounded-full bg-green-500/10 ...">
        <!-- calendar icon -->
      </div>
      <article>
        <h1 class="text-2xl font-bold text-green-600 ...">{{ formattedDate }}</h1>
        <p class="text-sm text-gray-500 ...">Scheduled Burials</p>
      </article>
    </div>

    <!-- Summary -->
    <div class="mb-4 text-sm text-gray-500 ...">
      {{ burials.length }} burial(s) scheduled for this date
    </div>

    <!-- Table -->
    <div class="bg-white dark:bg-neutral-900 border ... rounded-xl shadow-md overflow-hidden">
      <table v-if="burials.length > 0" class="min-w-full divide-y ...">
        <thead class="bg-gray-50 dark:bg-neutral-800">
          <tr>
            <TableHeader>ID</TableHeader>
            <TableHeader>Full Name</TableHeader>
            <TableHeader>Lot</TableHeader>
            <TableHeader>Time</TableHeader>
          </tr>
        </thead>
        <tbody class="divide-y ...">
          <tr v-for="burial in burials" :key="burial.id"
              @click="viewBurial(burial)"
              class="cursor-pointer bg-white dark:bg-neutral-800 hover:bg-gray-50 ...">
            <TableData>{{ burial.id }}</TableData>
            <TableData>{{ burial.full_name }}</TableData>
            <TableData>{{ burial.lot }}</TableData>
            <TableData>{{ burial.time }}</TableData>
          </tr>
        </tbody>
      </table>

      <!-- Empty state -->
      <div v-else class="text-center py-12 text-gray-500 ...">
        No burials scheduled for this date.
      </div>
    </div>
  </div>
</template>
```

Script section:

```js
const props = defineProps({
    burials: { type: Array, required: true },
    date: { type: String, required: true },
});

const back = () => {
    router.visit(route("clerk.burial_schedules.index"));
};

const formattedDate = computed(() => {
    return new Date(props.date + "T00:00:00").toLocaleDateString("en-US", {
        year: "numeric",
        month: "long",
        day: "numeric",
    });
});

const viewBurial = (burial) => {
    router.visit(route("clerk.burial_records.show", burial.id));
};
```

---

## Files to Modify

| File | Change |
|------|--------|
| `app/Http/Controllers/Clerk/BurialScheduleController.php` | Add `showByDate()` method |
| `routes/clerk.php` | Add `GET /burial-schedules/date/{date}` route |
| `resources/js/Pages/Clerk/BurialSchedules/IndexView.vue` | Add `dayMaxEvents: 5`, `moreLinkClick`, `moreLinkText`, async `events` callback, loading skeleton |
| `resources/js/Pages/Clerk/BurialSchedules/DateView.vue` | **New** — date detail page with table |

---

## Files Unchanged

| File | Reason |
|------|--------|
| `BurialRecord` model | No schema changes |
| `DeceasedRecord` model | No schema changes |
| `TableHeader.vue`, `TableData.vue` | Reused as-is |
| `Dashboard.vue` layout | Reused as-is |

---

## Implementation Order

1. Add `showByDate()` to `BurialScheduleController`
2. Add route to `clerk.php`
3. Create `DateView.vue` with table and clickable rows
4. Update `IndexView.vue` — add `dayMaxEvents: 5`, `moreLinkClick`, `moreLinkText`
5. Add async `events` callback + loading skeleton (from lazy fetching plan)
6. Verify: `vendor/bin/pint`, `npx prettier --write`, `npm run build`

---

## Risk Notes

- **Route ordering:** `burial-schedules/date/{date}` is more specific than `burial-schedules` so it won't conflict. Both use GET.
- **Date validation:** The `showByDate()` method validates the `{date}` parameter format (`Y-m-d`) before querying. Invalid dates return 404.
- **Empty dates:** If a user manually navigates to a date with no burials, the page shows an empty state with a back link.
- **Time zone:** Dates are stored as `date` type in MySQL (no timezone). The `date` prop is passed as-is (`Y-m-d` string). Display formatting uses `en-US` locale.
- **Lazy fetching dependency:** The `moreLinkClick` handler and async `events` callback require the `BurialScheduleApiController` from the lazy fetching plan. If that plan is not implemented yet, the calendar will fall back to loading all events statically (current behavior) — the `dayMaxEvents` and `moreLinkClick` will still work on the static data.
