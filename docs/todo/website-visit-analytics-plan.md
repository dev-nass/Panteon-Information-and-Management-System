# Website Visit Analytics — Implementation Plan

## Overview

Add self-hosted website visit tracking to the Panteon Information and Management System, with a visual analytics dashboard at `/admin/website-visits`. Counts visits to the **public pages only** (landing page and visitor interactive map), and reports page views, unique visitors, sessions, audience breakdown (device / browser / OS / geography), and a live visitor counter.

No third-party analytics service, no tracking pixels, no external cookies. All data lands in the project's own MySQL database and is visible to admins only. Built to run on the current Railway deployment (app + worker + cron services, MySQL, database-backed cache/queue/sessions).

**Decisions locked with the user:**

| Decision | Choice |
|---|---|
| Approach | Self-hosted inside this Laravel app |
| Scope | Public pages only (authenticated admin/clerk traffic excluded) |
| Metrics | Page views, unique visitors, sessions, audience breakdown, live visitor counter |
| Infrastructure | Add a Redis service on Railway and use it for cache + queue |
| Retention | 24 months of raw rows; daily rollups kept forever |

**Out of scope:** traffic-source/referrer dashboard (referrer columns are still captured, just not charted), CSV/PDF export, funnel/conversion tracking, staff/clerk usage analytics, admin page-level analytics.

---

## Current State

| Area | Status |
|---|---|
| Analytics schema | ❌ None. No `page_views`, `visits`, or equivalent table exists (22 tables, none matching view/visit/stat/analytic) |
| Analytics code | ❌ None. The only `Visitor*` matches are `VisitorInteractiveMapController` / `VisitorInteractiveMapView.vue` (the public map) |
| Audit log | ✅ `activity_logs` exists — but it is a **staff action audit trail** only (who edited what), written synchronously via `app/Traits/LogsActivity.php`. Not reusable for traffic analytics |
| Reporting | ✅ `app/Services/DashboardService.php` — the established analytics pattern (`{labels, values}` return shape, hourly/weekly/monthly series) |
| Charting | ✅ Chart.js + vue-chartjs via `resources/js/Components/Charts/{BarChart,DoughnutChart,HorizontalBarChart}.vue`. No new frontend deps needed |
| Infra | ⚠️ `CACHE_STORE=database`, `QUEUE_CONNECTION=database`, `SESSION_DRIVER=database`. Redis is configured in `config/database.php:150-183` but non-functional (no `phpredis` extension, no server) |
| Queued jobs | ⚠️ `app/Jobs` does not exist. `ShouldQueue` appears only on the 3 Mailables; only `ContactMail` is actually queued (`app/Http/Controllers/ContactController.php:23`) |

### Railway deployment facts that shape this design

| Fact | Consequence |
|---|---|
| Three services: app (`railway/init-app.sh`), worker (`railway/run-worker.sh`), cron (`railway/run-cron.sh`) | New queue + new scheduled commands must survive multi-service execution |
| **The scheduler runs in two containers** — `init-app.sh` ends with `php artisan schedule:work &` *and* `run-cron.sh` loops `schedule:run` every 60s | Every scheduled task currently runs twice. New tasks **must** use `onOneServer()` |
| `run-worker.sh` hardcodes `--queue=default` | A new `analytics` queue is never drained unless this file changes |
| Railway sets `X-Real-IP` to the real client IP (overwritten at the edge, not client-spoofable). `trustProxies(at: '*')` already set at `bootstrap/app.php:36` | Use `X-Real-IP` as the source of truth, not `$request->ip()` |
| Railway provides **no** geo header (`x-railway-ip-country` is still an open feature request on Station) | Geography requires a local IP→country database |
| `User::hasActiveSession()` (`app/Models/User.php:80-94`) queries `config('session.table')` directly | **Must NOT** move `SESSION_DRIVER` to Redis — it would break single-session enforcement |
| Tests run on SQLite `:memory:` with `QUEUE_CONNECTION=sync`, `CACHE_STORE=array` (`phpunit.xml:22-27`); `RefreshDatabase` is commented out in `tests/Pest.php:14` | All new SQL must be portable across MySQL and SQLite. Redis-only code needs a test double |
| `app/Services/DashboardService.php` uses MySQL-only functions (`TIMESTAMPDIFF`, `CURDATE`, `HOUR`) and therefore cannot be tested | Do not repeat that mistake |

---

## Target State

### Tables

#### Raw layer — source of truth, pruned at 24 months

**`visits`** — one row per visit/session:

```
visits
├── id                  (bigint, PK, auto-increment)
├── visitor_id          (uuid — first-party cookie, 1 year lifetime)
├── session_token       (string(64), unique — our own rolling 30-minute visit token)
├── referrer_host       (string, nullable)
├── referrer_url        (string, nullable)
├── landing_path        (string — normalized, no query string)
├── landing_route       (string, nullable — Ziggy route name)
├── device_type         (string: 'mobile' | 'tablet' | 'desktop')
├── browser             (string(50), nullable)
├── os                 (string(50), nullable)
├── country_code        (char(2), nullable — ISO 3166-1 alpha-2)
├── city                (string, nullable)
├── page_view_count     (unsignedSmallInteger, default 1)
├── duration_seconds    (unsignedInteger, default 0)
├── started_at          (timestamp)
├── last_activity_at    (timestamp)
├── created_at          (timestamp)
└── updated_at          (timestamp)
```

Indexes: `visitor_id`, `started_at`, `(started_at, device_type)`

**`page_views`** — one row per page view:

```
page_views
├── id                  (bigint, PK, auto-increment)
├── visit_id            (bigint, FK → visits.id, cascadeOnDelete)
├── visitor_id          (uuid)
├── path                (string — normalized, no query string)
├── route_name          (string, nullable)
├── duration_seconds    (unsignedSmallInteger, default 0)
├── viewed_at           (timestamp)
├── created_at          (timestamp)
└── updated_at          (timestamp)
```

Indexes: `viewed_at`, `path`, `(viewed_at, path)`

#### Rollup layer — never pruned, tiny

**`website_daily_stats`** — one row per day:

```
website_daily_stats
├── stat_date                   (date, PK)
├── page_views                  (unsignedBigInteger, default 0)
├── unique_visitors             (unsignedBigInteger, default 0)
├── new_visitors                (unsignedBigInteger, default 0)
├── sessions                    (unsignedBigInteger, default 0)
├── bounces                    (unsignedBigInteger, default 0)
├── total_duration_seconds      (unsignedBigInteger, default 0)
├── created_at                  (timestamp)
└── updated_at                  (timestamp)
```

**`website_daily_breakdowns`** — one row per (date, dimension, value):

```
website_daily_breakdowns
├── id                (bigint, PK, auto-increment)
├── stat_date         (date)
├── dimension         (string(20): 'path' | 'device' | 'browser' | 'os' | 'country' | 'city')
├── dimension_value   (string(255))
├── page_views        (unsignedBigInteger, default 0)
├── unique_visitors   (unsignedBigInteger, default 0)
├── created_at        (timestamp)
└── updated_at        (timestamp)
```

Unique: `(stat_date, dimension, dimension_value)` · Index: `(dimension, stat_date)`

### Page: `/admin/website-visits`

**Layout (mirrors `resources/js/Pages/Admin/DashboardView.vue`):**
- Header: "Website Visits" title, `text-3xl font-bold text-green-600 dark:text-green-400`
- Live visitors pill — visitors in the last 5 minutes, polled every 15s
- Filter bar: period tabs (Today / Yesterday / Last 7 days / Last 30 days / Last 12 months) + year selector for long ranges
- Stat card row (reuses `Components/Dashboard/StatCard.vue`):
  - Page Views (with % delta vs previous period)
  - Unique Visitors
  - Sessions
  - Avg Session Duration / Bounce Rate
- Charts row (reuses `Components/Charts/*.vue`):
  - **Traffic Over Time** — BarChart, x-axis = bucket, y-axis = count, Page Views + Unique Visitors series
  - **Devices** — DoughnutChart, mobile / tablet / desktop
  - **Browsers** — HorizontalBarChart, top 8
  - **Operating Systems** — HorizontalBarChart, top 8
  - **Countries / Regions** — HorizontalBarChart, top 10
- **Top Pages** — HorizontalBarChart, top 10 paths by page views
- All charts server-driven via `router.get(..., { preserveState: true })`
- Empty states reuse the existing `h-64 flex items-center justify-center rounded-xl bg-white dark:bg-neutral-800 border ...` block

### Sidebar Entry

Add a "Website Visits" link in `resources/js/Components/Dashboard/Sidebar.vue`, admin-only, beside "Activity Log" in the "Main" section. Uses a bar-chart / eye icon.

---

## Backend Changes

### 0. Redis on Railway (prerequisite, no app code)

1. Railway dashboard → **Add Redis** to the project, generate reference variable `REDIS_URL`.
2. `.env.production` — follow the existing reference-variable pattern already used for `DB_URL="${{MySQL.MYSQL_URL}}"`:
   ```
   REDIS_URL="${{Redis.REDIS_URL}}"
   CACHE_STORE=redis
   QUEUE_CONNECTION=redis
   ```
   Leave `SESSION_DRIVER=database` unchanged (see `User::hasActiveSession()` in Current State).
3. Verify `phpredis` is present in the Railway image. If not, set `REDIS_CLIENT=predis` and add `predis/predis` — **this is a dependency change and needs approval**.
4. `config/database.php:150-183` already reads `env('REDIS_URL')` for the default connection, so no config edit is required.

*Why this must come first:* every later phase depends on atomic locks (`onOneServer`, `WithoutOverlapping`) and cheap counters. On the current `database` cache store these technically work, but every lock and counter serialize through the same MySQL instance already serving 20k-row spatial map queries.

### 1. Migrations

- `database/migrations/2026_XX_XX_create_visits_table.php`
- `database/migrations/2026_XX_XX_create_page_views_table.php`
- `database/migrations/2026_XX_XX_create_website_daily_stats_table.php`
- `database/migrations/2026_XX_XX_create_website_daily_breakdowns_table.php`

All must be plain `Schema::create` calls — **no raw `ALTER TABLE` / `MODIFY COLUMN`**, and **no SQLite-unsafe statements**, since `phpunit.xml:26` runs the suite on SQLite `:memory:`.

### 2. Models

`app/Models/Visit.php`, `app/Models/PageView.php`, `app/Models/WebsiteDailyStat.php`, `app/Models/WebsiteDailyBreakdown.php`

Use a `casts()` method (not the `$casts` property) per Laravel 12 convention. Add `HasFactory` and create the matching factories in `database/factories/` for the two raw models. Relation: `PageView::visit()` → `BelongsTo`.

No `app/Enums` directory exists and the project uses plain string literals for `activity_logs.action` — follow that, do not introduce an enum base folder.

### 3. Middleware — `app/Http/Middleware/TrackPageView.php`

Register in `bootstrap/app.php:31-34`, appended to the `web` group alongside `HandleInertiaRequests` and `EnsureSingleSession`, so it has access to cookies.

Self-filtering — bail out unless **all** of these hold:
- `$request->isMethod('GET')`
- not `$request->ajax()`, not `$request->expectsJson()`, not `$request->is('api/*')`
- path is not `/up` (Railway health check)
- route name is in the public allowlist: `visitor.index`, `visitor.map.index`
- no authenticated user (`$request->user() === null`)
- `User-Agent` does not match the bot blocklist

**`handle()`** — resolve-or-create the two cookies and attach to the response, then build the payload:
- `panteon_vid` — `Str::uuid()`, 1-year expiry, `SameSite=Lax`, `Secure` in production. First-party identifier only; **no fingerprinting**.
- `panteon_vst` — 32-char random token, 30-minute rolling expiry. A new value means a new visit.
- Attach via `Cookie::queue()` so they ride out on the response (must happen here, not in `terminate()`).

**`terminate()`** — dispatch `RecordPageView` onto the `analytics` queue. **Nothing writes to MySQL on the response path.**

### 4. Job — `app/Jobs/RecordPageView.php`

`ShouldQueue`, `public $tries = 3`, `public $timeout = 10`, dispatched to the `analytics` queue.

Responsibilities:
1. `upsert` the `visits` row keyed on `session_token` — increments `page_view_count`, extends `last_activity_at`, accumulates `duration_seconds`.
2. `insert` the `page_views` row.
3. Update the live-visitor ZSET (see §6).

> ⚠️ `app/Jobs` is a **new base directory** and does not currently exist. AGENTS.md forbids creating new base folders without approval — see Open Questions.

### 5. Worker — `railway/run-worker.sh`

Change `--queue=default` to `--queue=default,analytics` (or add a second `queue:work` line for the analytics queue).

**Without this change the feature silently records nothing in production** — jobs would accumulate in the `jobs` table forever.

### 6. Services — `app/Services/WebsiteAnalytics/`

New subfolder under the existing `app/Services` base.

**`RequestClassifier.php`** — pure, testable, no I/O:
- `shouldTrack(Request): bool` — the filter list from §3
- `clientIp(Request): ?string` — prefer `X-Real-IP`, fall back to `$request->ip()`
- `normalizePath(string): string` — strip query string, collapse numeric/uuid segments so `/clusters/12` and `/clusters/15` don't become distinct rows
- `device(?string $userAgent): string` — mobile / tablet / desktop
- `browser(?string $userAgent): ?string`, `os(?string $userAgent): ?string`
- `isBot(?string $userAgent): bool` — small blocklist (bot, crawler, spider, curl, wget, headless, monitoring, Lighthouse, uptime)

**`GeoResolver.php`** — `resolve(?string $ip): array{country_code: ?string, city: ?string}`

Chain: `CF-IPCountry` header (if Cloudflare is ever placed in front of Railway) → local IP→country database → `null`. Must never throw; geo is best-effort.

**`VisitRecorder.php`** — the write path, called from the job. Owns the `visits` upsert and `page_views` insert.

**`AnalyticsAggregator.php`** — computes one day of rollups from the raw tables. Idempotent (`updateOrInsert` keyed on `stat_date` / the unique breakdown triple) so re-runs and backfills are safe.

**`AnalyticsReporter.php`** — the read path. Returns `{labels, values}` series matching `DashboardService`'s contract, for `period ∈ {today, yesterday, 7d, 30d, 12m}`, plus previous-period deltas for the KPI cards, plus breakdowns for device / browser / os / country / path. Ranges ≤ 90 days read `page_views`; longer ranges read the rollups.

**`LiveVisitorTracker.php`** — interface, plus a Redis implementation. Sorted set `analytics:live`, member = `visitor_id`, score = unix timestamp. On each recorded view: `ZADD`, then `ZREMRANGEBYSCORE` to drop entries older than the 5-minute window. Read = `ZCARD`. Backed by a `NullLiveVisitorTracker` bound when Redis is unreachable (and used under test, where `CACHE_STORE=array`).

### 7. Commands

**`app/Console/Commands/AggregateWebsiteVisits.php`** → `visits:aggregate`
- Options: `--date=` (specific day, for backfill), `--live` (only today, for the hourly refresh)
- Rebuilds `website_daily_stats` + `website_daily_breakdowns` for the target date(s)
- Fully idempotent

**`app/Console/Commands/PruneWebsiteVisits.php`** → `visits:prune`
- Chunked `DELETE` of `page_views` and `visits` older than 24 months (default; `--months=` override)
- Rollups untouched
- Chunking keeps memory use inside the Railway container's limit

### 8. Scheduler — `routes/console.php`

Add `Schedule::useCache('redis');` at the top so scheduler locks use Redis rather than the default store, then:

```php
Schedule::command('visits:aggregate')->dailyAt('00:15')->onOneServer()->withoutOverlapping(30);
Schedule::command('visits:aggregate --live')->hourly()->onOneServer()->withoutOverlapping(15);
Schedule::command('visits:prune')->monthlyOn(1, '03:00')->onOneServer()->withoutOverlapping(60);
```

`onOneServer()` is **mandatory** — without it the duplicate scheduler (app container + cron container) runs each of these twice. `onOneServer()` requires a shared lock-capable cache store, which is why Phase 0 comes first.

### 9. Controller — `app/Http/Controllers/Admin/WebsiteVisitController.php`

Single `index()` taking a raw `Request` — no Form Request — matching the hand-filtered style of `app/Http/Controllers/Admin/ActivityLogController.php`. Renders `Admin/WebsiteVisits/IndexView` with:

```
stats         → { page_views, unique_visitors, sessions, avg_duration_seconds,
                  bounce_rate, page_views_delta, unique_visitors_delta, ... }
trend_data    → { labels: [...], values: [...], unique: [...] }
breakdown_data→ { device: {labels, values}, browser: {...}, os: {...}, country: {...} }
top_pages     → { labels: [...], values: [...] }
live          → { active: int }        (Inertia::defer)
filters       → { period: string }
```

`live` uses `Inertia::defer()` so the counter can be polled without re-sending the chart payloads.

### 10. Routes — `routes/admin.php`

One line inside the existing `admin` group, after the activity-log route:

```php
Route::get('/website-visits', [WebsiteVisitController::class, 'index'])->name('website_visits.index');
```

### 11. Beacon (optional) — `routes/web.php` + controller

`POST /visitor/ping` (`visitor.ping`), throttled via the existing rate-limiter pattern in `app/Providers/AppServiceProvider.php`, backed by a controller method — **not a closure**, see Risks §1.

Without this, `duration_seconds` stays 0 and bounce rate degrades to "visited exactly one page".

---

## Frontend Changes

### `resources/js/Pages/Admin/WebsiteVisits/IndexView.vue` (new)

- `defineOptions({ layout: Dashboard })`
- `import Dashboard from "@/Layouts/Dashboard.vue"`
- snake_case prop names matching the controller keys
- Reuse `@/Components/Dashboard/StatCard.vue`, `@/Components/Dashboard/DashboardFiltersModal.vue`, `@/Components/Form/Button.vue`
- Reuse `@/Components/Charts/{BarChart,DoughnutChart,HorizontalBarChart}.vue` with the existing `chartData` / `chartOptions` prop contract
- Live counter: `import { usePoll } from '@inertiajs/vue3'; usePoll(15000)` — Inertia v2 auto-stops on unmount and throttles in background tabs
- Period tabs: local `ref` mirrors for instant feedback, then `router.get(route("admin.website_visits.index"), { period }, { preserveState: true })` — identical to `Admin/DashboardView.vue`'s `changeFilter`
- Single root `<div class="p-6 space-y-6">`; Tailwind v4 utilities; full `dark:` variants
- Dynamic horizontal-chart height driven by label count, mirroring `DashboardView.vue`'s `geoChartHeight` computed
- `@inertia-vue-development` and `@tailwindcss-development` skills apply

### `resources/js/Components/Dashboard/Sidebar.vue`

Add the "Website Visits" entry to the admin `roleRoutes` map beside Activity Log.

### `resources/js/app.js` (only if the beacon is included)

A small `inertia:navigate` / `pagehide` listener that `navigator.sendBeacon`s time-on-page.

---

## Files to Create

| Path | Purpose |
|---|---|
| `database/migrations/2026_XX_XX_create_visits_table.php` | Raw visits table |
| `database/migrations/2026_XX_XX_create_page_views_table.php` | Raw page views table |
| `database/migrations/2026_XX_XX_create_website_daily_stats_table.php` | Daily rollup |
| `database/migrations/2026_XX_XX_create_website_daily_breakdowns_table.php` | Dimension rollup |
| `database/factories/VisitFactory.php` | Test fixtures |
| `database/factories/PageViewFactory.php` | Test fixtures |
| `app/Models/Visit.php` | Visits model |
| `app/Models/PageView.php` | Page views model |
| `app/Models/WebsiteDailyStat.php` | Daily rollup model |
| `app/Models/WebsiteDailyBreakdown.php` | Dimension rollup model |
| `app/Http/Middleware/TrackPageView.php` | Terminable collection middleware |
| `app/Jobs/RecordPageView.php` | Queued writer ⚠️ new base dir |
| `app/Services/WebsiteAnalytics/RequestClassifier.php` | Filtering + dimension parsing |
| `app/Services/WebsiteAnalytics/GeoResolver.php` | IP → country/city |
| `app/Services/WebsiteAnalytics/VisitRecorder.php` | Write path |
| `app/Services/WebsiteAnalytics/AnalyticsAggregator.php` | Rollup computation |
| `app/Services/WebsiteAnalytics/AnalyticsReporter.php` | Read path |
| `app/Services/WebsiteAnalytics/LiveVisitorTracker.php` | Redis live counter |
| `app/Console/Commands/AggregateWebsiteVisits.php` | `visits:aggregate` |
| `app/Console/Commands/PruneWebsiteVisits.php` | `visits:prune` |
| `app/Http/Controllers/Admin/WebsiteVisitController.php` | Dashboard controller |
| `resources/js/Pages/Admin/WebsiteVisits/IndexView.vue` | Dashboard page |
| `tests/Feature/WebsiteVisitTrackingTest.php` | Collection path |
| `tests/Feature/WebsiteVisitAggregationTest.php` | Rollup + prune |
| `tests/Feature/Admin/WebsiteVisitAnalyticsTest.php` | Dashboard + access control |

## Files to Modify

| Path | Change |
|---|---|
| `bootstrap/app.php:31-34` | Append `TrackPageView::class` to the `web` group |
| `routes/admin.php` | Add the `website_visits.index` route |
| `routes/console.php` | Add `Schedule::useCache('redis')` + 3 scheduled commands |
| `railway/run-worker.sh` | Consume the `analytics` queue |
| `resources/js/Components/Dashboard/Sidebar.vue` | Sidebar link |
| `.env.production` | `REDIS_URL`, `CACHE_STORE=redis`, `QUEUE_CONNECTION=redis` |
| `.env.example` | Document the new Redis vars |
| `docs/todo/deployment/deployment-cron-setup.md` | Document the new schedule entries |
| `resources/js/app.js` | Only if the beacon is included |

## Files Unchanged

- `app/Models/ActivityLog.php`, `app/Traits/LogsActivity.php` — staff audit trail, unrelated
- `app/Services/DashboardService.php` — existing admin analytics, left as-is
- `config/database.php`, `config/cache.php`, `config/queue.php`, `config/session.php` — defaults already read the right env vars
- `app/Http/Middleware/EnsureSingleSession.php` — single-session enforcement untouched
- `routes/web.php` — untouched unless the beacon is included
- `package.json` — no new frontend dependencies

---

## Implementation Order

1. **Redis on Railway** — dashboard `Add Redis`, set the three env vars, redeploy, confirm `php artisan tinker --execute 'Cache::put("k",1); dump(Cache::get("k"));'`
2. **Migrations + models + factories** — `php artisan make:model Visit -f` etc. Verify on SQLite (tests) and MySQL (dev)
3. **`RequestClassifier` + `GeoResolver`** — pure logic first, unit-testable
4. **`TrackPageView` middleware + `RecordPageView` job** — register middleware, wire the `analytics` queue
5. **`railway/run-worker.sh`** — do this before the first deploy that enables tracking, or nothing gets written
6. **`LiveVisitorTracker`** — Redis ZSET
7. **`AnalyticsAggregator` + both commands + scheduler entries** — with `onOneServer()`
8. **`AnalyticsReporter` + `WebsiteVisitController` + route**
9. **Vue page + sidebar link**
10. **Tests**, then `vendor/bin/pint --dirty --format agent`
11. **Deploy**, backfill with `visits:aggregate --date=` if needed

---

## Verification

**Automated (Pest 3, following the manual-migrate pattern of `tests/Feature/Admin/DashboardTest.php` since `RefreshDatabase` is commented out in `tests/Pest.php:14`):**

- `WebsiteVisitTrackingTest` — visitor cookie issued on first hit and reused on the second; a second cookie set means a second visit; authenticated requests are not tracked; `POST`, `api/*`, `/up` and bot UAs are not tracked; paths with query strings are normalized; numeric segments collapse
- `WebsiteVisitAggregationTest` — `visits:aggregate` produces correct PV / UV / sessions / bounces for a fixture day; a second run is idempotent; `--date=` backfill works; `visits:prune` keeps in-window rows and removes older ones
- `Admin/WebsiteVisitAnalyticsTest` — page renders with `AssertableInertia`; 403 for clerk and guest; `filters.period` is echoed back; `live` is deferred

Run: `php artisan test --compact --filter=WebsiteVisit`, then the full suite on request.

**Manual (on Railway, after deploy):**

- [ ] `php artisan schedule:list` lists the three new entries
- [ ] `visits:aggregate` ran at `00:15` — `website_daily_stats` has a row for yesterday
- [ ] Redis is the live cache/queue store (`config('cache.default') === 'redis'`)
- [ ] `/admin/website-visits` renders with a non-empty dataset for a fresh day
- [ ] Live counter moves when browsing `/` in another browser
- [ ] `jobs` table is **not** growing (worker is draining the analytics queue)
- [ ] `storage/logs/laravel.log` clean of repeated errors after 24h
- [ ] Raw row counts match expectation at ~500 visits/day

---

## Risks & Blocking Issues

1. **`route:cache` likely fails in `railway/init-app.sh`.** `routes/web.php:8` and `routes/clerk.php:19` register routes with closures, which `Route::prepareForSerialization()` rejects with *"Unable to prepare route [...] for serialization. Uses Closure."* With `set -e` at the top of `init-app.sh`, that aborts the script — meaning `view:cache`, `queue:restart` and `schedule:work` never run. **Check the Railway deploy log to confirm before relying on any scheduled task.** Converting those two closures to controller methods would fix it; the new `visitor.ping` beacon route must therefore use a controller method too.
2. **The duplicate scheduler already double-runs backups.** The three `backup:*` tasks in `routes/console.php:11-13` have no `onOneServer()`, so under the current two-container setup they run twice daily. Out of scope for this feature, but a one-line fix worth making at the same time.
3. **Live counter accuracy depends on no edge HTML caching.** If Railway or a CDN starts caching `/` and `/map`, page views are undercounted and the live number drops toward zero. Verify response `Cache-Control` after deploy.
4. **App container memory.** `schedule:work` runs inside the same container as the web server under `init-app.sh`. `visits:prune` therefore uses chunked deletes.
5. **Geo dependency needs approval.** Railway has no geo header, so geography requires either a new Composer package or a bundled dataset (see Open Questions).

---

## Open Questions

1. **Geography without a Railway geo header.** Recommendation: `ip2location/ip2location-php` with the **LITE** country dataset — CC BY-SA 4.0, ~1.5 MB CSV, committable to the repo, no API key, no license server, works on Railway's ephemeral filesystem. Alternative: `geoip2/geoip2` + MaxMind GeoLite2 for city-level resolution, but it needs `MAXMIND_LICENSE_KEY`, cannot be committed (license), and triggers a ~60 MB download on every deploy. Both are **new Composer dependencies, requiring approval per AGENTS.md**. Zero-dependency option: put Cloudflare in front of Railway and read `CF-IPCountry` for country only.
2. **`app/Jobs/` is a new base directory** and does not exist. AGENTS.md forbids creating new base folders without approval. OK to create it, or should `RecordPageView` live inside `app/Services/WebsiteAnalytics/`?
3. **Time-on-page beacon** (Phase 11) — in or out? Without it, bounce rate is only "visited exactly one page" and average duration is always 0.
4. **Top Pages widget** — included by default, because "page views" is meaningless without a page dimension. Keep, or drop?
5. **CSV/PDF export** of visit data, mirroring `app/Http/Controllers/Admin/GenerateReportController.php`? Currently out of scope.