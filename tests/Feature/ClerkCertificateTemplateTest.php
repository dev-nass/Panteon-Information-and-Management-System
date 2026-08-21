<?php

use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_18_014948_create_certificate_templates_table.php']);

    Storage::fake('local');

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123456789',
        'role' => 'admin',
    ]);
});

function samplePdfUpload(): UploadedFile
{
    $path = base_path('tests/Fixtures/certificate_template_sample.pdf');

    return new UploadedFile(
        $path,
        'certificate_template_sample.pdf',
        'application/pdf',
        null,
        true
    );
}

it('requires authentication to access certificate template pages', function () {
    $this->get(route('clerk.certificate_templates.index'))->assertRedirect(route('login'));

    $this->post(route('clerk.certificate_templates.store'))->assertRedirect(route('login'));

    $this->delete(route('clerk.certificate_templates.destroy', 1))->assertRedirect(route('login'));

    $this->get(route('clerk.certificate_templates.file', 1))->assertRedirect(route('login'));
});

it('only allows clerks to access certificate template pages', function () {
    actingAs($this->admin)
        ->get(route('clerk.certificate_templates.index'))
        ->assertForbidden();

    actingAs($this->admin)
        ->post(route('clerk.certificate_templates.store'))
        ->assertForbidden();

    $template = CertificateTemplate::factory()->create();

    actingAs($this->admin)
        ->delete(route('clerk.certificate_templates.destroy', $template))
        ->assertForbidden();

    actingAs($this->admin)
        ->get(route('clerk.certificate_templates.file', $template))
        ->assertForbidden();
});

it('lists certificate templates with uploader info', function () {
    CertificateTemplate::factory()->create([
        'name' => 'Standard Template',
        'uploaded_by' => $this->clerk->id,
    ]);

    actingAs($this->clerk)
        ->get(route('clerk.certificate_templates.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Clerk/CertificateTemplate/IndexView')
            ->has('templates', 1)
            ->where('templates.0.name', 'Standard Template')
            ->where('templates.0.uploaded_by.first_name', 'Test')
            ->where('templates.0.uploaded_by.last_name', 'Clerk'));
});

it('stores an uploaded pdf template', function () {
    actingAs($this->clerk)
        ->post(route('clerk.certificate_templates.store'), [
            'name' => 'COS Standard',
            'file' => samplePdfUpload(),
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Certificate template uploaded successfully.');

    $template = CertificateTemplate::firstOrFail();

    expect($template->name)->toBe('COS Standard');
    expect($template->uploaded_by)->toBe($this->clerk->id);

    Storage::disk('local')->assertExists($template->file_path);
    expect($template->file_path)->toStartWith('certificate_templates/')
        ->and($template->file_path)->toEndWith('.pdf');
});

it('rejects a non-pdf upload', function () {
    actingAs($this->clerk)
        ->post(route('clerk.certificate_templates.store'), [
            'name' => 'Invalid',
            'file' => UploadedFile::fake()->create('template.docx', 100),
        ])
        ->assertSessionHasErrors('file');

    expect(CertificateTemplate::count())->toBe(0);
});

it('rejects an oversized pdf upload', function () {
    $tempPath = tempnam(sys_get_temp_dir(), 'oversized').'.pdf';
    copy(base_path('tests/Fixtures/certificate_template_sample.pdf'), $tempPath);
    file_put_contents($tempPath, str_repeat('x', 11 * 1024 * 1024), FILE_APPEND);

    $upload = new UploadedFile($tempPath, 'oversized.pdf', 'application/pdf', null, true);

    actingAs($this->clerk)
        ->post(route('clerk.certificate_templates.store'), [
            'name' => 'Too Big',
            'file' => $upload,
        ])
        ->assertSessionHasErrors('file');

    expect(CertificateTemplate::count())->toBe(0);
});

it('streams the template file for download', function () {
    Storage::disk('local')->put('certificate_templates/abc.pdf', file_get_contents(base_path('tests/Fixtures/certificate_template_sample.pdf')));

    $template = CertificateTemplate::factory()->create([
        'name' => 'COS Standard',
        'file_path' => 'certificate_templates/abc.pdf',
        'uploaded_by' => $this->clerk->id,
    ]);

    actingAs($this->clerk)
        ->get(route('clerk.certificate_templates.file', $template))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');
});

it('returns 404 when the template file is missing', function () {
    $template = CertificateTemplate::factory()->create([
        'file_path' => 'certificate_templates/does-not-exist.pdf',
    ]);

    actingAs($this->clerk)
        ->get(route('clerk.certificate_templates.file', $template))
        ->assertNotFound();
});

it('returns 404 for a non-pdf template', function () {
    Storage::disk('local')->put('certificate_templates/legacy.docx', 'content');

    $template = CertificateTemplate::factory()->create([
        'file_path' => 'certificate_templates/legacy.docx',
    ]);

    actingAs($this->clerk)
        ->get(route('clerk.certificate_templates.file', $template))
        ->assertNotFound();
});

it('destroys a template and its file', function () {
    Storage::disk('local')->put('certificate_templates/abc.pdf', 'content');

    $template = CertificateTemplate::factory()->create([
        'file_path' => 'certificate_templates/abc.pdf',
        'uploaded_by' => $this->clerk->id,
    ]);

    actingAs($this->clerk)
        ->delete(route('clerk.certificate_templates.destroy', $template))
        ->assertRedirect()
        ->assertSessionHas('success', 'Certificate template deleted successfully.');

    expect(CertificateTemplate::count())->toBe(0);
    Storage::disk('local')->assertMissing('certificate_templates/abc.pdf');
});
