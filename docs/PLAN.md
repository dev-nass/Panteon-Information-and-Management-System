# Improvement Plan

Suggestions organized by priority, based on codebase analysis.

---

## High Priority

### 1. Test Coverage
The system has zero tests for actual business logic — only 2 example stubs.
- **Burial record CRUD** (clerk + admin) — cover the service layer with transactions
- **Import flow** — XLSX/CSV parsing and record creation
- **Report generation** — verify PDF/Excel output correctness
- **Authentication flows** — login, password reset (6-digit code), clerk registration via invitation
- **API spatial queries** — `MBRContains`, cluster/lot lookups
- **Role middleware** — verify 403 for unauthorized roles

### 2. API Authentication
Most API routes (`/data/*`, `/api/*`) are publicly accessible. If these are meant to be internal only, consider applying auth middleware or at least throttling. If public map data is intentional, document and add rate limiting.

### 3. Rate Limiting on API Endpoints
Only `login` and `register` have rate limits. Add throttling to:
- `/data-search/burials` — prevent scraping
- `/data/partial-burials` — expensive spatial query under map pan/zoom
- `/admin/import` — prevent large file abuse

### 4. N+1 Query Risks
The codebase has TODOs acknowledging N+1 issues in map data controllers. Audit and eager-load:
- `BurialRecord` → `deceasedRecord`, `lot.cluster.phase`
- `Cluster` → `lots.burialRecords`
- Map data endpoints listing nested relations

---

## Medium Priority

### 5. Consolidate Duplicate Controllers
`Admin\BurialRecordController` and `Clerk\BurialRecordController` share similar logic (same for `LotManagementController`, `InteractiveMapController`, `DashboardController`). Consider:
- A single `BurialRecordController` that checks role for permission gating
- Or keep separate but extract shared logic into service classes (already partially done with `BurialRecordService`)

### 6. Standardize API Error Responses
API controllers return inconsistent error shapes. Define a consistent JSON envelope:
```json
{ "data": ..., "errors": ..., "message": "..." }
```
Use Laravel API exception handling in `bootstrap/app.php`.

### 7. Spatial Query Performance
`MBRContains` on the `partial-burials` endpoint could be slow under high concurrency. Ensure:
- Spatial indexes are applied (schema already has them)
- Consider caching static spatial data (phases, clusters) via Laravel's `Cache` store
- Set minimum zoom-level thresholds before firing spatial queries

### 8. Typo in `PhaseePlottingModal.vue`
The component is named `PhaseePlottingModal.vue` (extra "e"). Rename to `PhasePlottingModal.vue` and update all imports.

### 9. Typo in `docs/Ratelimter.md`
Should be `RateLimiter.md`. Fix the filename.

### 10. Missing `config/boost.php`
Laravel Boost config is not published. Run `php artisan boost:install` or `php artisan vendor:publish --tag=boost-config` to enable Boost features.

---

## Low Priority

### 11. Database Seeders
No seeders exist for demo data. Create a `Database\Seeders\DatabaseSeeder` that:
- Creates admin + clerk users
- Generates sample phases, clusters, lots with geometry
- Populates burial records with deceased data

### 12. Model Factory Quality
- `ClusterFactory` reuses `PhaseFactory` instead of having its own definition
- `DeceasedRecordFactory` exists but may not cover all nullable fields
- Add missing states (e.g., `->state(['corpse_disposal' => 'cremation'])`)

### 13. Frontend Code Splitting
Pages like `Admin/DashboardView.vue` and `Clerk/DashboardView.vue` appear to have substantial inline logic. Extract reusable composables:
- `useDashboardStats()`
- `useMapFilters()`
- `useBurialRecordForm()`

### 14. Consistent Breadcrumbs / Navigation
The sidebar distinguishes admin vs clerk menus, but there's no breadcrumb component. Deep pages (e.g., burial record create/edit in a specific lot) would benefit from breadcrumb navigation.

### 15. Vue Component Organization
Map modals are 12 files in a flat `Components/Map/` directory. Consider grouping by feature:
```
Map/
├── Modals/
│   ├── PhaseModal.vue
│   ├── ClusterModal.vue
│   └── LotModal.vue
├── Plotting/
│   ├── PlottingWrapper.vue
│   ├── PhasePlotting.vue
│   ├── ClusterPlotting.vue
│   └── LotPlotting.vue
└── Search.vue
```

### 16. Deferred Props / Lazy Loading
Inertia v2 supports deferred props. Consider using them for:
- Heavy dashboard statistics queries
- Map data on initial page load (load map tiles first, data on interaction)

### 17. Environment Separation
`.env` has debug mode on with Mailtrap credentials. Create proper:
- `.env.production` template
- Document required env vars in `.env.example`
- Remove hardcoded mail credentials (use env vars only)

### 18. Feature Flags for LGBTQ+ Field
`part_of_LGBTQ` with `prefer_not_to_say` in `deceased_records` is a sensitive field. Ensure:
- Audit logs track who views/edits it
- Reports can exclude it optionally
- It's handled with proper data privacy considerations under PH law (Data Privacy Act)
