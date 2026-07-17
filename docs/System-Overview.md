# Panteon Information and Management System — System Overview

## What It Is
A cemetery/lot management system for **Panteon de Dasmarinas**. Built with **Laravel 12**, **Inertia.js v2**, **Vue 3**, and **Tailwind CSS v4**, backed by a **MySQL** database with spatial geometry support for interactive map features.

## Architecture
- **Backend**: Laravel 12 (PHP 8.3) with service/repository pattern, form requests, and Eloquent API resources.
- **Frontend**: Inertia SPA with Vue 3, Chart.js, FullCalendar, Leaflet map, Preline UI components.
- **Auth**: Traditional email/password login with 6-digit code password reset flow. Rate-limited (10/min login, 10/hr registration).
- **Queue/Cache/Session**: All backed by MySQL (`database` driver).
- **Mail**: Mailtrap (SMTP sandbox) for dev.

## User Roles

| Role | Capabilities |
|------|-------------|
| **Visitor** | Public landing page, interactive map view |
| **Clerk** | Dashboard, burial records CRUD, burial schedules calendar, map (read-only), lot management (read-only) |
| **Admin** | Everything above + reports (PDF via DomPDF, Excel via FastExcel), user management, clerk invitations, XLSX/CSV import, full lot management CRUD |

## Key Domain Concepts

### Cemetery Structure (3-tier hierarchy)
```
Phase (e.g. "Phase 1", "Phase 2")
  └── Cluster (e.g. "Block A", type: underground/apartment/columbarium)
       └── Lot (grid position: column + row)
```

### Core Records
- **BurialRecord** — links a deceased person to a lot, recorded by a user (clerk)
- **DeceasedRecord** — detailed personal info, death details, disposal method, family info
- **Applicant** — the person who arranged the burial

### Map Visualization
- All spatial entities (phases, clusters, lots, junctions, pathways) use MySQL `geometry` columns with SRID 4326
- A graph of **Junctions** (entrance, intersection, endpoint nodes) and **Pathways** (weighted edges with distance) enables pathfinding
- Map renders with Leaflet; admins can draw/plot/phase/cluster/lot polygons directly

## Report Generation
- **DomPDF** — burial, deceased, summary, and phase PDF reports from Blade templates
- **FastExcel** — Excel/CSV export of the same data

## Data Import
- Admins can bulk-import XLSX/CSV files to create deceased, applicant, and burial records in one go

## API Endpoints (JSON)
- `/data/burials` — all burial records with GeoJSON
- `/data/phases` — all phases
- `/data/cluster/{id}/burials` — burials within a cluster
- `/data/partial-burials` — spatial bounds query (MBRContains)
- `/data-search/burials` — search by deceased name or burial ID
- `/api/junctions` / `/api/pathways` — pathfinding graph data
- Various `/data/phase`, `/data/cluster`, `/data/lot` — lot management lookups

## Middleware
- `admin` middleware — requires `role === 'admin'`
- `clerk` middleware — requires `role === 'clerk'`
- `HandleInertiaRequests` — shares `auth.user` and flash messages with all Inertia pages

## Tests
- Pest PHP testing framework
- Factories available for: User, Phase, Cluster, Lot, BurialRecord, Applicant, Junction, Pathway
