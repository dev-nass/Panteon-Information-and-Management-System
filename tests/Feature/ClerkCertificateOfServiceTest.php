<?php

use App\Models\ActivityLog;
use App\Models\BurialRecord;
use App\Models\CertificateTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

    // Minimal stand-ins for spatial tables that SQLite cannot migrate
    DB::statement('CREATE TABLE phases (id INTEGER PRIMARY KEY AUTOINCREMENT)');
    DB::statement('CREATE TABLE clusters (id INTEGER PRIMARY KEY AUTOINCREMENT, phase_id INTEGER NOT NULL)');
    DB::statement('CREATE TABLE lots (id INTEGER PRIMARY KEY AUTOINCREMENT, cluster_id INTEGER NOT NULL)');

    $this->artisan('migrate', ['--path' => 'database/migrations/2026_03_01_075233_create_burial_records_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_18_014948_create_certificate_templates_table.php']);

    Storage::fake('local');

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);

    $this->burialRecord = BurialRecord::create();
});

function certificatePayload(): array
{
    return [
        'deceased_name' => 'Juan Dela Cruz',
        'deceased_address' => '123 Sample St.',
        'date_of_death' => '2026-01-01',
        'place_of_death' => 'Sample City',
        'date_of_depository' => '2026-01-05',
        'burial_place' => 'Panteon',
        'applicant_name' => 'Maria Dela Cruz',
        'applicant_address' => '123 Sample St.',
        'relationship' => 'Spouse',
    ];
}

it('requires authentication to generate a certificate', function () {
    $this->post(route('clerk.certificate_of_service.generate', $this->burialRecord))
        ->assertRedirect(route('login'));
});

it('logs certificate generation without a template', function () {
    actingAs($this->clerk)
        ->post(route('clerk.certificate_of_service.generate', $this->burialRecord), certificatePayload())
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    expect(ActivityLog::count())->toBe(1);

    $log = ActivityLog::first();

    expect($log->user_id)->toBe($this->clerk->id)
        ->and($log->action)->toBe('generated')
        ->and($log->subject_type)->toBe(BurialRecord::class)
        ->and($log->subject_id)->toBe($this->burialRecord->id)
        ->and($log->description)->toBe('Generated certificate of service for Juan Dela Cruz');
});

it('logs the template name when generating with a template', function () {
    Storage::disk('local')->put(
        'certificate_templates/tpl.pdf',
        file_get_contents(base_path('tests/Fixtures/certificate_template_sample.pdf')),
    );

    $template = CertificateTemplate::create([
        'name' => 'Standard Template',
        'file_path' => 'certificate_templates/tpl.pdf',
        'uploaded_by' => $this->clerk->id,
    ]);

    actingAs($this->clerk)
        ->post(route('clerk.certificate_of_service.generate', $this->burialRecord), certificatePayload() + [
            'template_id' => $template->id,
        ])
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/pdf');

    $log = ActivityLog::first();

    expect($log->action)->toBe('generated')
        ->and($log->description)->toBe('Generated certificate of service for Juan Dela Cruz using template "Standard Template"');
});

it('does not log when validation fails', function () {
    actingAs($this->clerk)
        ->post(route('clerk.certificate_of_service.generate', $this->burialRecord), [])
        ->assertSessionHasErrors();

    expect(ActivityLog::count())->toBe(0);
});
