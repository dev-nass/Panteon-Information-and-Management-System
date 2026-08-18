# UI Adjustments Log

> Reference doc for all UI changes made to the Panteon Information and Management System.
> Read this FIRST whenever the prompt involves UI work so previously agreed-upon
> decisions are not broken or reverted. Append new entries at the end when a UI
> change is made.

## Key Conventions

- **Chart components are self-contained cards** (`BarChart`, `DoughnutChart`, `HorizontalBarChart`): they render their own card shell with a fixed `h-100` (400px) height. Do NOT wrap them in `dashboard-card` + fixed-height containers (`h-80`, `h-64`, `h-96`) — the 400px chart overflows the fixed wrapper and overlaps the next card. Use heading + chart directly (see `Admin/ActivityLog/IndexView.vue` for the reference pattern).
- **Wide tables scroll internally below `lg`**: only the `<table>` goes inside an `overflow-x-auto` wrapper with a min-width (e.g. `min-w-[880px] lg:min-w-full`). Search/action rows and pagination stay OUTSIDE the overflow so they are always visible at screen width. The min-width makes the table scroll automatically whenever the container is narrower (incl. medium screens with the sidebar open) and expand to fill at `lg+`.
- **Mobile navbar**: the dashboard `Header` is `fixed top-0`; on screens below `md` (sidebar hidden) the `<main>` needs `pt-16` so content clears the navbar (`md:pt-0` restores desktop).

---

## 1. Dashboard Layout — `resources/js/Layouts/Dashboard.vue`

- Added `pt-16 md:pt-0` to `<main>`: on small screens (<md, sidebar hidden) content is pushed down below the fixed mobile navbar; unchanged at md+.

## 2. Admin Dashboard — `resources/js/Pages/Admin/DashboardView.vue`

- **Header + tabs**: below `md`, the "Dashboard" title/subtitle sits on its own row and the Summary/Phases/Clusters tabs get a full-width row below it (buttons stretch via `flex-1 md:flex-none`). Side-by-side again at `md+`.
- **Filter tabs** (Today/Weekly/Monthly/Yearly): own full-width row below the dashboard (same `flex-1 md:flex-none` pattern).
- **Year selector**: own row, but NOT full width (`w-fit`).
- **Stat cards**: 2x2 grid on small screens (`grid-cols-2 lg:grid-cols-4`).
- **Charts (summary/phases/clusters tabs)**: removed the extra `dashboard-card` + fixed-height wrappers that made charts overlap when stacked (see Key Conventions). Charts render as heading + chart with `space-y-6` margins when stacked on small screens.
- **Filter pill overflow**: the filter tab row uses `flex-wrap` + `whitespace-nowrap` on buttons so the active green highlight never overflows the gray pill on narrow screens.

## 3. Generate Report — `resources/js/Pages/Admin/GenerateReport/IndexView.vue`

- **Form overflow fix**: card wrapper `min-w-[600px]` → `w-full` so the form fits the screen width on small screens (no forced 600px + horizontal scroll).

## 4. Clerk Invitation — Create — `resources/js/Pages/Admin/ClerkInvitation/CreateView.vue`

- **Form overflow fix**: card wrapper `min-w-[580px]` → `w-full`.
- Bottom action row: added `flex-wrap gap-2` so "View Invitation Logs" / "Send Invitation" don't overflow on narrow screens.

## 5. Clerk Invitation — Index — `resources/js/Pages/Admin/ClerkInvitation/IndexView.vue`

- **Table scroll containment**: removed the outer `-m-1.5 overflow-x-auto` wrapper (it scrolled the whole card). Search row + pagination are now fixed; only the table scrolls inside `overflow-x-auto`, table `min-w-[880px] lg:min-w-full` (auto-scrolls whenever the container is narrower, incl. md with sidebar open).
- **Header row**: single flex row at all sizes — search grows (`flex-1 min-w-0 max-w-xs md:max-w-md`), Filter button is auto-width at the right end (`ms-auto`), NEVER full width.
- **Dates**: Sent At / Expires At / Used At render as month-date format ("August 16, 2026") via `toLocaleDateString("en-US", { year, month: "long", day })` instead of the long `toLocaleString()`.
- **Back button**: `← Back` at the top of the page → `admin.clerk_invitations.create`. (Deliberately on IndexView, NOT on CreateView.)

## 6. User Management — Index — `resources/js/Pages/Admin/UserManagement/IndexView.vue`

- **Table scroll containment**: same treatment as §5 — only the table scrolls (`overflow-x-auto`, `min-w-[880px] lg:min-w-full`); header + pagination fixed.
- **Header row**: below `lg` the search input takes the full width on its own row, and the three action buttons (Invite Clerk, Export CSV, Filter) sit below it in a wrapping row. At `lg+`: side-by-side — search `lg:flex-1 lg:min-w-0 lg:max-w-md`, buttons `lg:ms-auto lg:justify-end`.

## 7. Activity Log — Index — `resources/js/Pages/Admin/ActivityLog/IndexView.vue`

- **Filter bar**: below `md` the range tabs (Today/7 Days/30 Days/All) get a full-width row (buttons stretch via `flex-1 md:flex-none`) with `flex-wrap` + `whitespace-nowrap` (green active highlight never overflows the gray pill); the Action select sits on its own row. Side-by-side again at `md+`.
- **Stat cards**: 2-column grid on small screens (`grid-cols-2 md:grid-cols-3`) instead of a single column.
- **Actions Per User header**: below `md` the heading sits on its own row and the Table/Bar Graph toggle gets a full-width row below (`flex-1 md:flex-none` buttons). Side-by-side at `md+`.
- **Table scroll containment**: same treatment as §5/§6 — search header + pagination stay fixed; only the table scrolls inside `overflow-x-auto` with `min-w-[880px] lg:min-w-full`.
- **User search input**: full width on small screens (`w-full md:max-w-md`).
- **Details panel** (property diff popover): `w-80` → `w-full max-w-80` so it never overflows the viewport on small screens.

## 8. Database Backup — Index — `resources/js/Pages/Admin/Backup/IndexView.vue`

- **UI naming**: page heading is "Database Backup" (was "Database Backups"); sidebar label is "Database Backup" (was "Backup"). Page folder is `Pages/Admin/DatabaseBackup/IndexView.vue` (renamed from `Backup`). Routes/controller remain `admin.backup.*` / `BackupController`.
- **Table scroll containment**: same treatment as §5–§7 — removed the outer `-m-1.5 overflow-x-auto` / `p-1.5 min-w-full inline-block` wrappers; only the table scrolls inside `overflow-x-auto` with `min-w-[880px] lg:min-w-full`. Header + actions stay fixed.
- **Filename readability**: the filename never wraps (`whitespace-nowrap` instead of `break-all`) and the Filename column keeps a `min-w-56` (224px) minimum on all screens — on small screens the full filename stays on one line and is readable via horizontal table scroll.

## 9. Lot Management — Index — `resources/js/Pages/Shared/LotManagement/IndexView.vue` (+ table components)

- **Header**: stacks below `lg` (was `md`) — at `lg+` search/tabs/buttons sit side-by-side (`lg:flex lg:justify-between`); below `lg` each gets its own row.
- **Tab selection spacing (Phase/Cluster/Lot)**: the pill is full width below `lg` (`w-full lg:w-fit`) with equal-width segments (`flex-1 lg:flex-none`) + `flex-wrap` + `whitespace-nowrap` — tabs are evenly spaced on small and medium screens and the active highlight never overflows.
- **Search input**: full width below `lg` (`w-full lg:max-w-md`).
- **Context breadcrumb**: `flex-wrap` so Phase → Cluster → Lot chips wrap instead of overflowing on small screens.
- **Tables (PhaseTable, ClusterTable, LotTable)**: same scroll containment as §5–§8 — `overflow-x-auto` wrapper (below `lg`) with `min-w-[880px] lg:min-w-full`; pagination + loading skeleton stay outside the wrapper.
- **Pagination (ClusterTable, LotTable)**: below `sm` the "Showing X to Y of Z" text and the page buttons stack into two rows (`flex flex-col gap-3 sm:flex-row`); the button group wraps (`flex-wrap gap-2`); page numbers are windowed — max 7 items (e.g. `1 … 4 5 6 … 20`) via `visiblePages`, so the number of buttons shown stays small on small/medium screens.

## 10. Burial Records — Index — `resources/js/Pages/Shared/BurialRecords/IndexView.vue`

- **Table scroll containment**: same treatment as §5–§9 — removed the outer `-m-1.5 overflow-x-auto` / `p-1.5 min-w-full inline-block` wrappers; only the table scrolls (`overflow-x-auto`, `min-w-[880px] lg:min-w-full`).
- **Pagination**: moved inside the card (below the scrollable table, `border-t` divider) so it's fixed at screen width and never scrolls with the table; page links wrap (`flex flex-wrap gap-2`) on small screens.
- **Header**: stacks below `lg` — search full width (`w-full lg:max-w-md`); Create/Filter/Type/Toggle buttons wrap (`flex-wrap justify-start sm:justify-end`) on their own rows instead of overflowing.

## 11. Import Records — Index — `resources/js/Pages/Admin/ImportRecord/IndexView.vue`

- **Form overflow fix**: removed the `-m-1.5 overflow-x-auto` / `p-1.5 min-w-[650px] inline-block` wrappers — on small screens the card is full width (`w-full`), and at `sm+` it keeps its original centered 650px layout (`sm:max-w-[650px]`).
- **Header**: wraps (`flex-wrap`) so icon + title stack instead of overflowing.
- **Drop area**: padding reduced below `sm` (`p-8 sm:p-[4rem]`).
- **Actions row** (View Import Logs / Start Import): `flex-wrap gap-2` so the buttons never overflow on small screens.

## 12. Burial Records — Show — `resources/js/Pages/Shared/BurialRecords/ShowView.vue`

- **Header**: below `lg` the deceased name row takes the full width and the action buttons (View on Map, Edit/Delete/COS — clerk) sit BELOW it (`flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between`); buttons wrap (`flex-wrap gap-x-3 gap-y-2`). At `lg+` the original side-by-side layout is retained.
- **Tabs**: horizontally scrollable — the tab bar is `overflow-x-auto` with an inner `min-w-max` flex row and `whitespace-nowrap` labels; the underline stays full-width below the scrollable tabs.

## 13. Clerk Dashboard — `resources/js/Pages/Clerk/DashboardView.vue`

Same treatment as the Admin Dashboard (§2):
- **Header**: below `md` the title/subtitle sit on their own row and the "Today is: <date>" badge sits below it at natural width (`w-fit`, left-aligned); side-by-side at `md+`.
- **Stat cards**: 2-column grid on small screens (`grid-cols-2 md:grid-cols-3 lg:grid-cols-5`).
- **Card headers** (Today's Field Schedule / Upcoming Schedules): `flex-wrap` so the count badges never overflow on small screens.
- **Schedule rows**: `flex-wrap gap-2` so the status badge drops to its own line instead of being squished off-screen on narrow phones.

## 14. Settings — Profile & Security — `resources/js/Pages/Settings/ProfileView.vue`, `ChangePasswordView.vue`

- **Sidebar**: Preferences link removed entirely — the sidebar lists only Profile and Security. The divider (`border-t`) at the bottom of the nav is retained. Below `lg` the nav becomes a wrapping horizontal row of links; at `lg+` it's the original 180px column.
- **Content fit**: the `grid-cols-[180px_1fr]` layout stacks below `lg` (`grid-cols-1 lg:grid-cols-[180px_1fr]`). Profile form fields are single column on mobile (`grid-cols-1 sm:grid-cols-2`); action buttons wrap (`flex-wrap justify-end gap-2`).

---

## Changelog

| Date | Section | Change |
|------|---------|--------|
| 2026-08-16 | 1–6 | Initial log of all small/medium-screen responsive UI adjustments |
| 2026-08-16 | 7 | Activity Log Index: stacked filter bar + toggle, 2-col stat cards, table scroll containment, full-width search, responsive details panel |
| 2026-08-16 | 8 | Database Backup: renamed UI labels, table scroll containment, filename nowrap + min-w-56 column |
| 2026-08-16 | 9 | Lot Management: stacked header below lg, full-width evenly-spaced tabs, wrapping breadcrumb, table scroll containment in all 3 table components |
| 2026-08-16 | 9 | Pagination (Cluster/Lot tables): stacks below sm, wraps, windowed page numbers (max 7 items with ellipsis) |
| 2026-08-16 | 10 | Burial Records Index: table scroll containment, pagination moved inside card + wrapping links, stacked/wrapping header below lg |
| 2026-08-16 | 11 | Import Records: removed 650px min-width wrapper (w-full below sm), wrapping header/actions, smaller drop-area padding below sm |
| 2026-08-16 | 12 | Burial Records Show: name full-width + action buttons below on <lg, wrapping buttons, scrollable tabs |
| 2026-08-16 | 13 | Clerk Dashboard: stacked header below md, 2-col stat cards on small, wrapping card headers + schedule rows |
| 2026-08-16 | 14 | Settings Profile/Security: Preferences moved to sidebar bottom with divider, sidebar stacks/wraps below lg, form fields single-col on mobile |
| 2026-08-18 | 17 | Certificate Templates (new): upload card + list with Fields Mapped badges, table scroll containment; Field Editor: per-page PDF canvas + overlay boxes, wrapping toolbar with Add Box toggle/field select/Save; COS Show: template dropdown + field-count label, Generating… button state |

## 17. Certificate Templates — Index & Editor — `resources/js/Pages/Clerk/CertificateTemplate/IndexView.vue`, `EditorView.vue`

- **Upload card**: same responsive treatment as Import Records (§11) — `w-full sm:max-w-[650px]`, wrapping header, reduced drop-area padding below `sm` (`p-8 sm:p-[3rem]`), wrapping action row.
- **Table scroll containment**: same treatment as §5–§10 — only the table scrolls (`overflow-x-auto`, `min-w-[880px] lg:min-w-full`); header with count badge stays fixed. Fields Mapped column uses green "N fields" / yellow "Not set yet" badges.
- **Editor toolbar**: wraps below `md` (`flex-wrap`); Add Box toggle, selected-box field `<select>`, Delete Box, unassigned-count badge, and Save sit in one row at `lg+`.
- **PDF pages**: each page renders at 1.5× into a bordered card with page-number header; the fabric overlay is absolutely positioned over it so boxes scale with the page.
- **COS Show**: template `<select>` is full-width (`md:col-span-2`) with helper text; error text under the dropdown; Generate shows "Generating…" and is disabled while filling.