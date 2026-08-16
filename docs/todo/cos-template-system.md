# Certificate of Service Template System

## Context

Currently, the Clerk's Certificate of Service (COS) feature generates a PDF using one of two Blade templates (`certificate_of_service_content.blade.php` for plain text, `certificate_of_service.blade.php` for full styling). There is no template selection, no file upload, and no persistence.

This plan adds a template management system where clerks upload `.docx` template files (containing `{{placeholder}}` syntax), and select from those templates (or choose "plain") when generating a COS document.

## The Flow

```
CLERK (template management)              CLERK (COS generation)
    |                                         |
    v                                         v
Upload .docx templates              Select template from dropdown
(with {{placeholders}})             or select "Plain"
    |                                         |
    v                                         v
Stored to disk + DB                  System replaces placeholders
                                     in the .docx, downloads as .docx
                                         |
                                         v
                                     Browser downloads Word file
```

---

## 1. New Dependency

```bash
composer require phpoffice/phpword
```

Required for parsing `.docx` files and replacing text placeholders. The project already uses `phpoffice/phpspreadsheet` (sibling package).

---

## 2. Migration — `certificate_templates` table

```php
Schema::create('certificate_templates', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('file_path');
    $table->foreignIdFor(User::class, 'uploaded_by')->nullable()->constrained()->nullOnDelete();
    $table->timestamps();
});
```

Storage location: `storage/app/private/certificate_templates/` (private disk).

---

## 3. Model — `CertificateTemplate`

**New file:** `app/Models/CertificateTemplate.php`

- `$fillable`: `['name', 'file_path', 'uploaded_by']`
- `uploadedBy()` → `BelongsTo(User::class)`
- No changes to existing models

---

## 4. Routes — `routes/clerk.php`

Add 3 new routes under the existing `clerk` middleware group:

| Method | URI | Name |
|---|---|---|
| GET | `/certificate-templates` | `clerk.certificate_templates.index` |
| POST | `/certificate-templates` | `clerk.certificate_templates.store` |
| DELETE | `/certificate-templates/{certificate_template}` | `clerk.certificate_templates.destroy` |

Existing COS routes remain unchanged.

---

## 5. New Controller — `Clerk/CertificateTemplateController`

**`index()`** — Fetch all `CertificateTemplate` records, render `Clerk/CertificateTemplate/IndexView`

**`store(Request $request)`** — Validate (`name` required, `file` required `.docx` max 10MB), store file as `Str::uuid() . '.docx'` to private disk, create DB record, redirect back with flash success

**`destroy(CertificateTemplate $certificate_template)`** — Delete file from disk + DB record, redirect back with flash success

---

## 6. Updated Controller — `Clerk/CertificatieOfServiceController`

### `show()` — add template list to props:

```php
$templates = CertificateTemplate::latest('created_at')->get(['id', 'name']);

return Inertia::render('Clerk/CertificateOfService/ShowView', [
    // ... existing props
    'templates' => $templates,
]);
```

### `generate()` — add template selection:

```php
$template_id = $request->validate([
    'template_id' => 'nullable|integer|exists:certificate_templates,id',
    // ... existing 9 fields
])['template_id'] ?? null;

if ($template_id) {
    // Uploaded template → PhpWord → replace placeholders → download .docx
    $template = CertificateTemplate::findOrFail($template_id);
    $docxPath = Storage::disk('private')->path($template->file_path);
    
    $phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
    $this->replacePlaceholders($phpWord, $data);
    
    $tempPath = storage_path('app/temp/' . Str::uuid() . '.docx');
    \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tempPath);
    
    return response()->download($tempPath, $template->name . '.docx')
        ->deleteFileAfterSend(true);
} else {
    // Plain → existing DomPDF flow (unchanged)
    $pdf = Pdf::loadView('certificates.certificate_of_service_content', ['data' => $data]);
    return $pdf->download('certificate_of_service_' . $burial_record->id . '.pdf');
}
```

### Helper: `replacePlaceholders($phpWord, $data)`

Walk all sections, tables, footers, and text runs. Find `{{field_name}}` patterns and replace with actual data values. Uses PhpWord's `searchElements()` API.

---

## 7. Vue Pages

### a) New: `Clerk/CertificateTemplate/IndexView.vue`

Following the existing import page pattern:
- Drag-and-drop upload zone (`.docx` only)
- Client-side validation: file type + 10MB max
- Template list table: name, uploaded date, delete button
- Flash success/error messages
- Uses `router.post()` with `FormData` for upload

### b) Update: `Clerk/CertificateOfService/ShowView.vue`

Add template dropdown above existing form fields:

```vue
<div class="md:col-span-2">
    <label>Certificate Template</label>
    <select v-model="form.template_id">
        <option :value="null">Plain (text-only PDF)</option>
        <option v-for="t in templates" :key="t.id" :value="t.id">
            {{ t.name }}
        </option>
    </select>
</div>
```

Update `form` ref to include `template_id: null`.
Update `generate()` to include `template_id` in hidden form fields.

### c) Update: `resources/js/Components/Dashboard/Sidebar.vue`

Add "Certificate Templates" link under Clerk section (after Burial Records, before Lot Management), with a `document-text` icon.

---

## 8. Placeholder Convention

The `.docx` templates use this syntax:

| Placeholder | Replacement |
|---|---|
| `{{deceased_name}}` | Deceased full name |
| `{{deceased_address}}` | Deceased address |
| `{{date_of_death}}` | Date of death |
| `{{place_of_death}}` | Place of death |
| `{{date_of_depository}}` | Date of depository |
| `{{burial_place}}` | Burial place |
| `{{applicant_name}}` | Applicant full name |
| `{{applicant_address}}` | Applicant address |
| `{{relationship}}` | Relationship to deceased |

The clerk creates the `.docx` in Microsoft Word, places these placeholders where data should appear, and uploads it.

---

## 9. Files to Create/Modify

| Action | File |
|---|---|
| **Create** | `app/Models/CertificateTemplate.php` |
| **Create** | `app/Http/Controllers/Clerk/CertificateTemplateController.php` |
| **Create** | `database/migrations/xxxx_create_certificate_templates_table.php` |
| **Create** | `resources/js/Pages/Clerk/CertificateTemplate/IndexView.vue` |
| **Modify** | `routes/clerk.php` |
| **Modify** | `app/Http/Controllers/Clerk/CertificatieOfServiceController.php` |
| **Modify** | `resources/js/Pages/Clerk/CertificateOfService/ShowView.vue` |
| **Modify** | `resources/js/Components/Dashboard/Sidebar.vue` |

---

## 10. Alternatives (Documented for Future Reference)

The following approaches are documented here for future consideration if the `.docx` download approach needs to be changed.

### Alternative A: PhpWord → HTML → DomPDF (PDF output)

Replace the `.docx` download with a PDF conversion pipeline:

```php
$phpWord = \PhpOffice\PhpWord\IOFactory::load($docxPath);
$this->replacePlaceholders($phpWord, $data);

$converter = new \PhpOffice\PhpWord\HTMLConverter();
$html = $converter->convert($phpWord);

$pdf = Pdf::loadHtml($html);
return $pdf->download('certificate_of_service_' . $burial_record->id . '.pdf');
```

**Pros:** Both plain and uploaded templates output PDF. No external dependencies beyond what's already installed.

**Cons:** Two-step conversion (PhpWord → HTML → PDF). Complex Word formatting (tables, images, custom fonts, precise spacing) may not translate perfectly. The PDF may not look exactly like the `.docx` template.

**Effort:** Low — mostly the same code, just changing the output step.

### Alternative B: LibreOffice CLI (pixel-perfect PDF)

Use LibreOffice in headless mode to convert the filled `.docx` directly to PDF:

```php
$this->replacePlaceholders($phpWord, $data);
$tempDocx = storage_path('app/temp/' . Str::uuid() . '.docx');
\PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($tempDocx);

$tempPdf = storage_path('app/temp/' . Str::uuid() . '.pdf');
exec("libreoffice --headless --convert-to pdf --outdir " . dirname($tempPdf) . " " . $tempDocx);

return response()->download($tempPdf, 'certificate_of_service_' . $burial_record->id . '.pdf')
    ->deleteFileAfterSend(true);
```

**Pros:** Pixel-perfect conversion. The PDF looks exactly like the `.docx` template. Both outputs are PDF.

**Cons:** Requires LibreOffice installed on the server (`sudo apt-get install libreoffice`). Adds a system dependency. Slower than pure PHP conversion.

**Effort:** Medium — need to handle LibreOffice installation, path configuration, and error handling.

### Alternative C: User's Custom Alternative

If the user wants a different approach (e.g., a custom template engine, a different file format, or a hybrid approach), this plan can be extended. Some possibilities:

- **Blade-based template rendering**: Convert the `.docx` to a Blade template at upload time, then use DomPDF for PDF generation. The `.docx` serves as the source, but the actual rendering uses Blade.
- **Template preview**: Show a preview of the filled template before download, allowing the clerk to verify the output.
- **Multiple format support**: Allow uploading `.docx` OR `.pdf` templates, with different handling for each.
- **Template versioning**: Track template revisions and allow clerks to use older versions.

---

## 11. Summary

| Aspect | Approach |
|---|---|
| **Primary output** | `.docx` download for uploaded templates, PDF for plain |
| **Template format** | `.docx` with `{{placeholder}}` syntax |
| **Template management** | Clerk-only (upload, list, delete) |
| **Storage** | Private disk (`storage/app/private/certificate_templates/`) |
| **Placeholder replacement** | PhpWord text run scanning |
| **PDF generation (plain)** | Existing DomPDF flow (unchanged) |
| **Future alternatives** | Documented (PhpWord→HTML→PDF, LibreOffice CLI, custom) |
