# Certificate Template Storage & 404 Preview Issue Explained

> Comprehensive explanation of why uploaded certificate templates returned a `404 Not Found` when previewed after a period of time, how the codebase was updated to handle it, and how to permanently prevent file loss in production.

---

## 1. Problem Statement

On `Clerk/CertificateTemplate/IndexView`, clerks upload `.pdf` certificate templates. Initially after uploading, the template could be previewed. However, after some time (or after deployments and container restarts), clicking the **"Preview"** button opened the modal with an embedded `<iframe>` displaying a `404 Not Found` error.

---

## 2. Root Causes

### A. Ephemeral Container Filesystems (Railway / Docker)
- **Database Persistence vs. Filesystem Reset:** In containerized environments like Railway (used by this application via `railway/init-app.sh`), the MySQL database runs on a separate, persistent service, while the web application runs inside a stateless container with an **ephemeral filesystem**.
- When a template is uploaded, `CertificateTemplateController::store()` writes the physical `.pdf` file to local container storage (`storage/app/private/certificate_templates/{uuid}.pdf`), and writes the template metadata to MySQL.
- Whenever a new deploy occurs (e.g. after git pushes to `main`) or the Railway container restarts/sleeps:
  - The container's local disk is destroyed and recreated from the clean git build.
  - The physical file in `storage/app/private/certificate_templates/` is permanently wiped because `storage/` is in `.gitignore` and not stored in git.
  - The MySQL database retains the `certificate_templates` table row.
- When the clerk visits `IndexView`, the template is loaded from the database and displayed in the table.
- When **"Preview"** is clicked, `CertificateTemplateController::file()` runs:
  ```php
  if (! str_ends_with($certificate_template->file_path, '.pdf')
      || ! Storage::disk('local')->exists($certificate_template->file_path)) {
      abort(404);
  }
  ```
  Because the physical file was erased on container recreation, `Storage::disk('local')->exists(...)` returns `false`, aborting with `404`.

---

### B. Storage Path Discrepancy (Laravel 11 vs Legacy `storage/app`)
- In Laravel 11/12, the `'local'` filesystem disk root was changed from `storage_path('app')` to `storage_path('app/private')`.
- Earlier documentation and commands (such as in `docs/todo/done/cos-template-system.md` and `docs/todo/deployment-php-upload-config.md`) referenced `storage/app/certificate_templates/`.
- If templates were copied, restored from backup, or mounted into `storage/app/certificate_templates/`, Laravel's `Storage::disk('local')` checks did not see them because it strictly looks inside `storage/app/private/`.

---

### C. Lack of UI Indicators & Fragile Generation Flow
- **No file status on Index:** `CertificateTemplateController::index()` previously mapped database records without checking if the physical file existed on disk.
- **Raw 404 in iframe:** `IndexView.vue` embedded the file URL in an iframe regardless of file presence, and did not key the iframe (`:key`), leading to cached 404 views.
- **500 error in Certificate of Service:** If a clerk attempted to generate a certificate using a missing template, `CertificatieOfServiceController` passed the missing file path directly into Ghostscript and FPDI, causing an unhandled 500 error.

---

## 3. Codebase Improvements Applied

### 1. `app/Http/Controllers/Clerk/CertificateTemplateController.php`
- **`index()`**: Added a `'file_exists'` boolean flag to each template returned to Inertia using `templateFileExists()`.
- **`file()`**: 
  - Validates `.pdf` extension.
  - Checks `Storage::disk('local')->exists($path)`.
  - Added fallback check to legacy path `storage_path('app/' . $path)` before failing.
  - Returns a descriptive 404 message (`'Certificate template file was not found in storage.'`) if missing from both locations.
- **`destroy()`**: Safely cleans up the file from either `Storage::disk('local')` or the fallback legacy path.

### 2. `app/Http/Controllers/Clerk/CertificatieOfServiceController.php`
- In `generate()`: Resolves the template path across both storage locations and verifies the file exists on disk *before* invoking Ghostscript (`downgradePdf`).
- If missing, it halts execution and redirects back with a user-friendly validation error on `template_id` instead of crashing.

### 3. `resources/js/Pages/Clerk/CertificateTemplate/IndexView.vue`
- **Table Indicator:** Displays a red `Missing File` badge next to the template name if `template.file_exists === false`.
- **Disabled Action:** The **Preview** button is disabled with reduced opacity for templates with missing files.
- **Toast Feedback:** Clicking preview on an invalid template triggers an informative toast: `"Template file is missing from server storage. Please delete and re-upload."`
- **Modal Fallback UI:** The modal now renders a helpful missing-file prompt instead of a blank 404 iframe if opened.
- **Keyed Iframe:** Added `:key="previewTemplate.id"` on the `<iframe>` so switching between previews recreates the frame cleanly.

### 4. `tests/Feature/ClerkCertificateTemplateTest.php`
- Added test coverage ensuring `file_exists` evaluates to `true` when the file is present on disk and `false` when missing.

---

## 4. How to Permanently Prevent File Loss in Production

To ensure uploaded files survive redeployments on containerized platforms (like Railway):

### Option A: Railway Persistent Volume (Recommended for Railway)
1. In the Railway project dashboard, select the web service.
2. Go to **Settings** → **Volumes** → **Add Volume**.
3. Set the **Mount Path** to:
   ```text
   /app/storage/app
   ```
4. Redeploy the service. All uploaded certificate templates in `storage/app/private/certificate_templates/` will persist across all future code deployments and container restarts.

### Option B: Cloud Object Storage (S3 / R2)
1. Create an S3-compatible bucket (e.g. AWS S3, Cloudflare R2, MinIO, or Supabase Storage).
2. Configure credentials in `.env`:
   ```env
   FILESYSTEM_DISK=s3
   AWS_ACCESS_KEY_ID=your-key-id
   AWS_SECRET_ACCESS_KEY=your-secret-key
   AWS_DEFAULT_REGION=auto
   AWS_BUCKET=your-bucket-name
   AWS_ENDPOINT=https://your-endpoint.com
   ```
3. Update `CertificateTemplateController` to use `Storage::disk(config('filesystems.default'))` and download temporary copies for Ghostscript/FPDI processing in `CertificatieOfServiceController`.
