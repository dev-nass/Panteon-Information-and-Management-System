# Activity Log "View" Functionality Enhancement Plan

## Overview

In the current application, the **Activity Log** (`/admin/activity-log` rendered via `resources/js/Pages/Admin/ActivityLog/IndexView.vue`) contains a "View" action button for each log entry. However, this button only presents meaningful information when the recorded action was **updating a burial record**. For almost all other actions—such as creating or deleting records, plot assignments, imports, certificate generations, clerk invitations, or backups—clicking "View" either reveals an empty dropdown with *"No property changes recorded"* or displays cramped, raw database keys.

This document records:
1. The complete scan of where `App\Traits\LogsActivity` (and direct `ActivityLog` creation) is used across the codebase.
2. The architectural root causes explaining why "View" currently fails for other actions.
3. The step-by-step target implementation plan to upgrade the trait, model, controllers, and frontend UX into a context-aware **Activity Details Modal**.

---

## Current State: Codebase Scan

### 1. The Logging Trait

The trait is defined in [`app/Traits/LogsActivity.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Traits/LogsActivity.php):

```php
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

### 2. Inventory of Trait Usages Across the Codebase

The trait is used in **6 controllers**, plus 1 controller creating `ActivityLog` entries directly, and 1 controller reading activity logs:

| File | Controller Method | Action | Subject Model | Properties Logged | Current "View" Experience in UI |
|---|---|---|---|---|---|
| [`app/Http/Controllers/Clerk/BurialRecordController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/BurialRecordController.php) | `store` | `created` | `BurialRecord` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Clerk/BurialRecordController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/BurialRecordController.php) | `update` | `updated` | `BurialRecord` | `old` & `new` (7 fields: name, dates, address) | ✅ **Works**: shows Old values vs New values |
| [`app/Http/Controllers/Clerk/BurialRecordController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/BurialRecordController.php) | `archive` | `archived` | `BurialRecord` | `new` (`reason`, notes) | ⚠️ Awkward: shows `reason: ...` under "New values" |
| [`app/Http/Controllers/Clerk/BurialRecordController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/BurialRecordController.php) | `restore` | `restored` | `BurialRecord` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Clerk/BurialRecordController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/BurialRecordController.php) | `destroy` | `deleted` | `BurialRecord` | `null` | ❌ *"No property changes recorded"* (all deleted info lost) |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `storePhase` | `created` | `Phase` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `storeCluster` | `created` | `Cluster` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `storeLot` | `created` | `Lot` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `storeBulkLot` | `created` | `Cluster` | `new` (`lot_count`) | ⚠️ Awkward: shows `lot_count: N` under "New values" |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `updatePhase` | `updated` | `Phase` | `old` & `new` (`phase_name`) | ⚠️ Shows raw key `phase_name` |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `updateCluster` | `updated` | `Cluster` | `old` & `new` (`cluster_name`, `type`, `capacity`) | ⚠️ Shows raw keys |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `updateLot` | `updated` | `Lot` | `old` & `new` (`column`, `row`) | ⚠️ Shows raw keys `column`, `row` |
| [`app/Http/Controllers/Admin/LotManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/LotManagementController.php) | `deletePhase`, `deleteCluster`, `deleteLot` | `deleted` | `Phase`, `Cluster`, `Lot` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/UserManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/UserManagementController.php) | `terminate` | `terminated` | `User` | `new` (`terminated_reason`, `notes`) | ⚠️ Raw key-value under "New values" |
| [`app/Http/Controllers/Admin/UserManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/UserManagementController.php) | `reinstate` | `reinstated` | `User` | `new` (`reinstated_reason`, `notes`) | ⚠️ Raw key-value under "New values" |
| [`app/Http/Controllers/Admin/UserManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/UserManagementController.php) | `update` (role) | `role_changed` | `User` | `old` & `new` (`role`) | ⚠️ Raw keys |
| [`app/Http/Controllers/Admin/UserManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/UserManagementController.php) | `update` (profile) | `updated` | `User` | `old` & `new` (`contact_number`, `role`) | ⚠️ Raw keys |
| [`app/Http/Controllers/Admin/UserManagementController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/UserManagementController.php) | `destroy` | `deleted` | `User` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/ClerkInvitationController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/ClerkInvitationController.php) | `store` | `created` | `ClerkInvitation` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/ImportingController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/ImportingController.php) | `store` | `imported` | `ImportedExcelLog` | `new` (`status`, `imported`, `skipped`) or `null` | ⚠️ Raw keys under "New values" or *"No property changes recorded"* |
| [`app/Http/Controllers/Clerk/CertificatieOfServiceController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Clerk/CertificatieOfServiceController.php) | `generate` | `generated` | `BurialRecord` | `null` | ❌ *"No property changes recorded"* |
| [`app/Http/Controllers/Admin/BackupController.php`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/app/Http/Controllers/Admin/BackupController.php) | `store`, `download`, `destroy` | `created`, `downloaded`, `deleted` | *(None / null)* | `null` (creates `ActivityLog` directly) | ❌ *"No property changes recorded"* |

---

## Root Cause Analysis

### 1. Inflexible Schema & Trait Assumption
The trait signature assumes that all activity logging consists solely of comparing `$oldValues` and `$newValues`. When an entity is **created**, there are no "old values"; when an entity is **deleted**, there are no "new values". Because callers passed `null` for both, `properties` became `null`.

### 2. Frontend Rigid Rendering Logic
In [`resources/js/Pages/Admin/ActivityLog/IndexView.vue`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/resources/js/Pages/Admin/ActivityLog/IndexView.vue#L457-L542):
```vue
<details class="group">
    <summary class="cursor-pointer list-none text-sm font-medium text-green-600 ...">
        View
    </summary>
    <div v-if="log.properties" class="...">
        <div v-if="propertyEntries(log.properties, 'old').length > 0">...</div>
        <div v-if="propertyEntries(log.properties, 'new').length > 0">...</div>
    </div>
    <p v-else>No property changes recorded</p>
</details>
```
* If `log.properties` is `null`, it renders the generic fallback text.
* The only caller that provides a full, multi-field before-and-after array is `BurialRecordController::update` (7 fields).
* For all other actions, the user either gets *"No property changes recorded"* or raw database column keys without formatting.

### 3. Missing Polymorphic Relationship & Links
The `ActivityLog` model stores `subject_type` and `subject_id`, but has **no `subject()` relationship**. Even if an admin wants to jump to the actual record (e.g. view the live burial record or lot), there is no link or route resolution.

### 4. UI Layout Limitations
The HTML `<details>` element sits directly inside a table cell (`<td>`). When expanded, it drastically stretches the table row height, displaces pagination, and clips long text.

### 5. Outdated Action Enum Mappings
The database enum in `database/migrations/2026_09_13_000004_extend_activity_logs_action_enum_terminated.php` contains:
`'created'`, `'updated'`, `'deleted'`, `'role_changed'`, `'imported'`, `'generated'`, `'archived'`, `'restored'`, `'terminated'`, `'reinstated'`.
However, `IndexView.vue` only lists 6 actions in its filter options and chart colors, omitting `archived`, `restored`, `terminated`, and `reinstated`.

---

## Target State Design

```
┌────────────────────────────────────────────────────────────────────────┐
│ Activity Log Index (/admin/activity-log)                               │
│                                                                        │
│ [Timestamp]  [User]   [Action Badge]   [Description]       [Action]    │
│ 10:45 AM     John D.  Created (Green)  Created burial...   [View 👁]   │
└───────────────────────────────────┬────────────────────────────────────┘
                                    │ Clicking "View" opens:
                                    ▼
┌────────────────────────────────────────────────────────────────────────┐
│ Activity Details Modal                                                 │
├────────────────────────────────────────────────────────────────────────┤
│ Header: [Action Badge] [Subject: Burial Record #104]  [Timestamp / IP] │
│ Actor:  Admin Maria Santos (maria@cemetery.gov)                        │
│ Status: Active in System  —  [Open Burial Record ↗]                    │
├────────────────────────────────────────────────────────────────────────┤
│ Body (Context-Aware):                                                  │
│                                                                        │
│ • If Updated / Role Changed: Side-by-side diff table:                  │
│   Field Name               Previous Value      New Value               │
│   Deceased Last Name       Cruz                Dela Cruz               │
│   Date of Depository       2026-09-01          2026-09-05              │
│                                                                        │
│ • If Created: Initial Snapshot Card                                    │
│   Deceased: Juan Dela Cruz | Born: 1950-01-01 | Died: 2026-08-30       │
│   Assigned Plot: Phase 1 / Cluster A / Lot 12-B                        │
│   Applicant: Maria Dela Cruz (Spouse)                                  │
│                                                                        │
│ • If Deleted: Historical Record Archive (Warning: Record Deleted)      │
│   Preserved attributes before deletion                                │
│                                                                        │
│ • If Terminated / Reinstated / Archived:                               │
│   Status Reason: Misconduct | Notes: Account suspended pending review  │
│                                                                        │
│ • If Imported: Excel Import Summary                                    │
│   File: masterlist.xlsx | Imported: 142 records | Skipped: 3           │
│                                                                        │
│ • If Generated: Document Generation Details                            │
│   Document: Certificate of Service | Issued For: Juan Dela Cruz        │
└────────────────────────────────────────────────────────────────────────┘
```

---

## Implementation Plan

### Phase 1: Trait & Model Upgrades

#### 1.1 Upgrade `App\Traits\LogsActivity`
Expand the trait to allow recording snapshots on creation and deletion, as well as custom metadata:
```php
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected function logActivity(
        string $action,
        ?Model $subject,
        string $description,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $extraProperties = null,
    ): ActivityLog {
        $properties = [];

        if ($oldValues !== null || $newValues !== null) {
            if ($oldValues) $properties['old'] = $oldValues;
            if ($newValues) $properties['new'] = $newValues;
        }

        if ($extraProperties) {
            $properties = array_merge($properties, $extraProperties);
        }

        return ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id' => $subject?->id,
            'description' => $description,
            'properties' => !empty($properties) ? $properties : null,
            'ip_address' => request()->ip(),
        ]);
    }
}
```

#### 1.2 Upgrade `App\Models\ActivityLog`
1. Add the polymorphic `subject()` relationship:
   ```php
   public function subject(): MorphTo
   {
       return $this->morphTo();
   }
   ```
2. Add accessors/methods to resolve:
   * `subject_title`: Human-readable label (e.g., `"Burial Record"`, `"Plot"`, `"User"`, `"Excel Import"`).
   * `subject_url`: Direct URL to view the live record in the admin portal (if active):
     * `BurialRecord` $\rightarrow$ `route('admin.burial_records.show', $this->subject_id)`
     * `User` $\rightarrow$ `route('admin.user_management.show', $this->subject_id)`
     * `Lot` $\rightarrow$ `route('admin.lot_management.show', $this->subject_id)`
     * `ImportedExcelLog` $\rightarrow$ `route('admin.import.index')`
     * `Phase` / `Cluster` $\rightarrow$ `route('admin.lot_management.index')`
   * `subject_exists`: Boolean check whether the related model still exists in the database.

---

### Phase 2: Instrument Controllers with Meaningful Payloads

Update each controller using `LogsActivity` to supply rich snapshot payloads:

#### 2.1 `BurialRecordController` (Clerk)
* **`store`**: Snapshot the newly created record:
  ```php
  $this->logActivity('created', $burialRecord, "...", extraProperties: [
      'snapshot' => [
          'deceased_name' => "{$burialRecord->deceasedRecord->first_name} {$burialRecord->deceasedRecord->last_name}",
          'date_of_death' => $burialRecord->deceasedRecord->date_of_death,
          'date_of_depository' => $burialRecord->deceasedRecord->date_of_depository,
          'plot' => "{$burialRecord->lot->cluster->phase->phase_name} / Cluster {$burialRecord->lot->cluster->cluster_name} / Lot {$burialRecord->lot->row}-{$burialRecord->lot->column}",
          'applicant' => "{$burialRecord->deceasedRecord->applicant->first_name} {$burialRecord->deceasedRecord->applicant->last_name}",
      ]
  ]);
  ```
* **`destroy`**: Snapshot the record *before* calling `$burial_record->delete()` so that historical data is preserved in the audit log.
* **`archive` / `restore`**: Save `reason` and `notes` into structured properties.

#### 2.2 `LotManagementController` (Admin)
* **`storePhase` / `deletePhase`**: Snapshot phase name and coordinate plot status.
* **`storeCluster` / `deleteCluster`**: Snapshot cluster name, type, capacity, and parent phase.
* **`storeLot` / `deleteLot`**: Snapshot row, column, cluster name, and phase name.
* **`storeBulkLot`**: Snapshot count of created lots and target cluster.

#### 2.3 `UserManagementController` (Admin)
* **`terminate` / `reinstate`**: Keep formatted `reason` and `notes`.
* **`destroy`**: Snapshot user's name, email, and role prior to account deletion.

#### 2.4 `ClerkInvitationController` (Admin)
* **`store`**: Snapshot invited email, role (`clerk`), and token expiration date.

#### 2.5 `ImportingController` (Admin)
* **`store`**: Snapshot filename, import type, total records, successful count, skipped count, and error summary.

#### 2.6 `CertificatieOfServiceController` (Clerk)
* **`generate`**: Snapshot burial record ID, deceased name, and generation date.

#### 2.7 `BackupController` (Admin)
* Replace direct `ActivityLog::create` with unified logging capturing backup filename, file size, and storage disk.

---

### Phase 3: Frontend UX Overhaul (`IndexView.vue`)

#### 3.1 Replace `<details>` with Activity Details Modal
In [`resources/js/Pages/Admin/ActivityLog/IndexView.vue`](file:///opt/lampp/htdocs/Projects2026/Panteon-Information-and-Management-System/resources/js/Pages/Admin/ActivityLog/IndexView.vue):
1. Replace the table cell `<details>` disclosure with an action button:
   ```html
   <button 
       @click="openDetailsModal(log)" 
       class="inline-flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400 hover:text-green-700"
   >
       <EyeIcon class="size-4" />
       View Details
   </button>
   ```
2. Build an **Activity Details Modal** (clean, responsive, dark-mode compatible) featuring:
   * **Header**: Action badge, full timestamp, relative time, and actor info (name, email, IP address).
   * **Subject Banner**: Target model label, record ID, and an **"Open Record ↗"** button if `subject_exists` is true. If deleted, show a *"Record Deleted"* warning badge.
   * **Context-Aware Content**:
     * **Updated / Role Changed**: Side-by-side or table comparison of changed fields with human-readable labels (e.g. `date_of_birth` $\rightarrow$ "Date of Birth").
     * **Created / Deleted**: Attribute grid displaying the snapshot of the record.
     * **Terminated / Reinstated / Archived**: Status change card displaying Reason and Notes.
     * **Imported**: Summary statistics (Processed, Imported, Skipped) with error badges.
     * **Generated**: Certificate type and deceased record details.
     * **Fallback**: Clean key-value viewer for arbitrary JSON properties.

#### 3.2 Add Missing Enum Actions to Filters and Charts
Update `actionOptions`, `actionBadgeColors`, `actionChartColors`, and `actionLabels` in `IndexView.vue` to include:
* `archived`
* `restored`
* `terminated`
* `reinstated`

---

## Verification & Testing Checklist

- [ ] **Unit / Feature Tests**:
  - Test that `BurialRecordController::store` creates an `ActivityLog` with a valid `snapshot` property.
  - Test that `BurialRecordController::destroy` creates an `ActivityLog` retaining deceased and plot info before deletion.
  - Test that `LotManagementController` logs snapshots for phase, cluster, and lot creations/deletions.
  - Test that `ActivityLog::subject()` resolves correctly when the model exists and returns `null` safely when deleted.
- [ ] **Frontend Verification**:
  - Verify "View Details" button opens the modal without distorting the table row layout.
  - Verify every action type (`created`, `updated`, `deleted`, `role_changed`, `imported`, `generated`, `archived`, `restored`, `terminated`, `reinstated`) displays appropriate, formatted content.
  - Verify the "Open Record" button navigates to the target page when the subject exists.
  - Verify action filter dropdown correctly filters logs for all 10 action types.
