# Admin Database Backup Feature

**Approach:** `spatie/laravel-backup` + admin UI + daily scheduled backup

---

## Backend

### 1. Install & configure

- `composer require spatie/laravel-backup`
- `php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"`
- Add `backups` disk in `config/filesystems.php` → `local`, root `storage_path('app/backups')`; point `config/backup.php` destination at it
- Set `default_keep_all_backups_for_days` / `default_keep_daily_backups_for_days` (pruning)

### 2. `app/Http/Controllers/Admin/BackupController.php`

- `index()` — list `.zip` files from the backups disk (name, size, last modified) → `Inertia::render('Admin/Backup/IndexView', ...)`
- `store()` — run `backup:run --only-db` via `Artisan::call()` (small local DB = fast; queued dispatch noted as alternative), then `ActivityLog::create([...])` mirroring the `LogsActivity` trait shape (app/Traits/LogsActivity.php:10) since there's no Backup model to log against
- `download($filename)` — `Storage::disk('backups')->download()` (filename whitelist-validated against existing files to prevent path traversal)
- `destroy($filename)` — delete file + activity log entry

### 3. Routes

Inside existing admin group in `routes/admin.php:13` (already has `auth|verified|admin` middleware):

| Method | URI | Name |
|--------|-----|------|
| `GET` | `/admin/backups` | `backup.index` |
| `POST` | `/admin/backups` | `backup.store` |
| `GET` | `/admin/backups/{filename}/download` | `backup.download` |
| `DELETE` | `/admin/backups/{filename}` | `backup.destroy` |

### 4. Scheduling

In `routes/console.php` (currently only has the `inspire` command):

```php
Schedule::command('backup:clean')->daily()->at('01:00');
Schedule::command('backup:run')->daily()->at('01:30');
```

> Requires a cron entry for `schedule:run` on the server — flag for deployment.

---

## Frontend

### 5. `resources/js/Pages/Admin/Backup/IndexView.vue`

Follows existing patterns (Admin/GenerateReport):

- "Backup Now" button with loading state
- Table of backups (name/date/size) with download + delete actions
- Flash feedback for success/error

### 6. `resources/js/Components/Dashboard/Sidebar.vue` (~line 600)

- Admin-only `SidebarLink` "Backup" next to Activity Log

---

## Verification

7. Pest feature test (`php artisan make:test --pest AdminBackupTest`): admin-only access, backup creation, listing, download, delete
8. Run `vendor/bin/pint --dirty`, `php artisan test --compact`, `npm run build`

---

## Out of Scope

- **Restore** — server-side `mysql < backup.sql` operation
- **DB credentials** — backups only include data, no secrets
