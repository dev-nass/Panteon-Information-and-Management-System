# Certificate of Service Template System

## Context

The Clerk's Certificate of Service (COS) feature generates a PDF using a Blade template (`certificate_of_service_content.blade.php`). The template management system allows clerks to upload a PDF (letterhead/official design) which is used purely as a visual background layer. The COS content is rendered by DomPDF from the Blade template and merged on top server-side using FPDI.

## The Flow (Final)

```
CLERK (template management)              CLERK (COS generation)
    |                                         |
    v                                         v
Upload PDF template                 Fill in COS form fields
(letterhead / official design)      Select template from dropdown
    |                                or select "Plain"
    v                                         |
Stored to disk + DB                           v
                                     POST to server
                                         |
                                         v
                                     DomPDF renders Blade template
                                     → content PDF (string)
                                         |
                                         v
                                     Ghostscript downgrades template
                                     PDF to 1.4 (FPDI-compatible)
                                         |
                                         v
                                     FPDI: template = background layer
                                     DomPDF output = foreground layer
                                     → merged PDF
                                         |
                                         v
                                     Browser downloads merged PDF
```

No placeholders in the template PDF. No client-side PDF manipulation.

---

## Files

### New (untracked)

| File                                                                           | Purpose                                                                     |
| ------------------------------------------------------------------------------ | --------------------------------------------------------------------------- |
| `app/Models/CertificateTemplate.php`                                           | Model — `name`, `file_path`, `uploaded_by`; `fields` column kept but unused |
| `app/Http/Controllers/Clerk/CertificateTemplateController.php`                 | `index`, `store`, `file`, `destroy`                                         |
| `database/migrations/2026_08_18_014948_create_certificate_templates_table.php` | `certificate_templates` table                                               |
| `database/factories/CertificateTemplateFactory.php`                            | Factory for tests                                                           |
| `resources/js/Pages/Clerk/CertificateTemplate/IndexView.vue`                   | Upload page — drag-and-drop, template list, delete                          |
| `resources/js/Pages/Clerk/CertificateTemplate/EditorView.vue`                  | **Orphaned** — box editor, no longer used or routed to; safe to delete      |
| `resources/js/lib/cosFields.js`                                                | `COS_FIELDS` array + `formatLongDate()` helper                              |
| `resources/js/lib/pdfRender.js`                                                | pdfjs page renderer — only used by orphaned EditorView; safe to delete      |
| `tests/Feature/ClerkCertificateTemplateTest.php`                               | Feature tests                                                               |
| `tests/Fixtures/certificate_template_sample.pdf`                               | Test fixture                                                                |
| `public/vendor/pdfjs/`                                                         | pdfjs standard fonts — only used by orphaned EditorView; safe to delete     |

### Modified

| File                                                                    | What changed                                                                                                                                  |
| ----------------------------------------------------------------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `routes/clerk.php`                                                      | Added `index`, `store`, `file`, `destroy` routes; removed `editor` and `fields` routes                                                        |
| `app/Http/Controllers/Clerk/CertificatieOfServiceController.php`        | `show()` passes `templates` list; `generate()` uses DomPDF + FPDI merge; `downgradePdf()` helper converts template to PDF 1.4 via Ghostscript |
| `resources/js/Pages/Clerk/CertificateOfService/ShowView.vue`            | Template dropdown; `generate()` submits native HTML form to server (no client-side PDF manipulation)                                          |
| `resources/views/certificates/certificate_of_service_content.blade.php` | DomPDF Blade template — `@page { margin: 220px 85px; size: A4 }`                                                                              |
| `resources/js/Components/Dashboard/Sidebar.vue`                         | Added "Certificate Templates" link in clerk sidebar                                                                                           |
| `package.json` / `package-lock.json`                                    | Added `pdfjs-dist`, `pdf-lib`, `fabric`, `puppeteer-core` — all now unused                                                                    |

---

## Routes

| Method | URI                                      | Name                                  |
| ------ | ---------------------------------------- | ------------------------------------- |
| GET    | `/clerk/certificate-templates`           | `clerk.certificate_templates.index`   |
| POST   | `/clerk/certificate-templates`           | `clerk.certificate_templates.store`   |
| GET    | `/clerk/certificate-templates/{id}/file` | `clerk.certificate_templates.file`    |
| DELETE | `/clerk/certificate-templates/{id}`      | `clerk.certificate_templates.destroy` |

---

## npm Packages Added

| Package          | Version    | Status                                                             |
| ---------------- | ---------- | ------------------------------------------------------------------ |
| `pdfjs-dist`     | `^6.2.108` | Unused — was used for client-side placeholder scanning (abandoned) |
| `pdf-lib`        | `^1.17.1`  | Unused — was used for client-side PDF overlay (abandoned)          |
| `fabric`         | `^7.4.0`   | Unused — was used for box editor (abandoned)                       |
| `puppeteer-core` | `^25.8.0`  | Unused devDependency                                               |

All four can be safely uninstalled (`npm uninstall pdfjs-dist pdf-lib fabric puppeteer-core`).

## Composer Packages Added

| Package         | Version | Purpose                                                         |
| --------------- | ------- | --------------------------------------------------------------- |
| `setasign/fpdi` | `^2.6`  | Imports existing PDF pages as FPDI templates (background layer) |
| `setasign/fpdf` | `^1.9`  | FPDF base class required by FPDI's `FpdfTpl`                    |

## System Dependencies

| Tool               | Version | Purpose                                                                                                                    |
| ------------------ | ------- | -------------------------------------------------------------------------------------------------------------------------- |
| Ghostscript (`gs`) | 10.02.1 | Downgrades uploaded template PDFs from PDF 1.5+ (compressed object streams) to PDF 1.4 so FPDI's free parser can read them |

---

## Final Implementation: FPDI Background Layer

The uploaded template PDF is used purely as a visual background (letterhead, logos, borders). The COS content is rendered by DomPDF from the Blade template. FPDI merges both server-side.

### Generation steps (`generate()` in `CertificatieOfServiceController.php`)

1. Validate form fields + optional `template_id`
2. `Pdf::loadView('certificates.certificate_of_service_content', [...])->output()` — renders Blade to PDF string
3. If no `template_id` — return DomPDF output directly as download
4. If `template_id` selected:
    - `downgradePdf()` — runs `gs -sDEVICE=pdfwrite -dCompatibilityLevel=1.4` on the stored template, writes to a temp file
    - Write DomPDF output to a second temp file
    - FPDI: `setSourceFile(templateTmp)` → loop pages → `AddPage` → `useTemplate(bgTpl)` (background)
    - `setSourceFile(contentTmp)` → `importPage($p)` → `useTemplate(fgTpl)` (foreground overlay)
    - `unlink()` both temp files
    - Return `$fpdi->Output('S')` as PDF download

### Why Ghostscript is needed

FPDI's free parser only supports PDF 1.4 and below (uncompressed cross-reference tables). Modern PDFs exported from Word/LibreOffice/Google Docs use PDF 1.5+ with compressed object streams. Ghostscript rewrites the file as PDF 1.4 before FPDI reads it.

---

## DB Schema

```php
Schema::create('certificate_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('file_path');
    $table->json('fields')->nullable(); // kept but unused
    $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
    $table->timestamps();
});
```

Storage location: `storage/app/certificate_templates/` (local disk).

---

## Abandoned Implementations

### 1. Box Editor (Fabric.js)

The original plan used a Fabric.js box editor (Sejda-style) where the clerk would draw rectangles over a PDF preview in `EditorView.vue`, assign a COS field to each box, and save coordinates to the `fields` JSON column. On generation, pdf-lib would write values at those saved coordinates.

Abandoned because coordinate alignment between Fabric.js (scaled canvas pixels), pdfjs (viewport units), and pdf-lib (PDF user space / bottom-up Y axis) was unreliable across different PDFs.

Removed: `editor()` and `updateFields()` controller methods, `editor` and `fields` routes, `EditorView.vue` (orphaned), `pdfRender.js` (orphaned), `fabric` npm package (unused).

### 2. Placeholder Text Scanning (pdfjs + pdf-lib, client-side)

The clerk typed `{{field_name}}` in Word/LibreOffice, exported to PDF, uploaded. Client-side pdfjs `getTextContent()` found placeholder positions; pdf-lib whited out and overwrote them.

Abandoned because pdf-lib only ships standard fonts (TimesRoman) while the PDF used Calibri/Arial — font metric mismatch caused whitespace and font-size misalignment that could not be resolved client-side.

Removed: `generateWithTemplate()` in `ShowView.vue`, all pdfjs/pdf-lib imports. `pdfjs-dist` and `pdf-lib` npm packages are still installed but unused.

#### Issue is that Panteon does not use underlines on their COS, only continuous text so whitespaces is not allowed
