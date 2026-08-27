<?php

use App\Models\Applicant;
use App\Models\DeceasedRecord;
use App\Models\User;
use App\Services\RecordNormalizationService;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);

    $this->normalizer = app(RecordNormalizationService::class);
});

it('blocks creation when same name and date_of_birth already exists', function () {
    DeceasedRecord::factory()->create([
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'date_of_birth' => '1950-01-15',
        'date_of_death' => '2020-06-20',
        'applicant_id' => Applicant::factory()->create(['contact_number' => '09123456789'])->id,
    ]);

    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Juan',
        'Dela Cruz',
        '1950-01-15',
        '2021-03-10'
    );

    expect($duplicate)->not->toBeNull()
        ->and($duplicate->first_name)->toBe('Juan')
        ->and($duplicate->last_name)->toBe('Dela Cruz');
});

it('blocks creation when same name and date_of_death already exists', function () {
    DeceasedRecord::factory()->create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'date_of_birth' => '1960-05-10',
        'date_of_death' => '2019-12-25',
        'applicant_id' => Applicant::factory()->create(['contact_number' => '09123456789'])->id,
    ]);

    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Maria',
        'Santos',
        '1962-03-15',
        '2019-12-25'
    );

    expect($duplicate)->not->toBeNull()
        ->and($duplicate->first_name)->toBe('Maria')
        ->and($duplicate->last_name)->toBe('Santos');
});

it('allows creation when name matches but dates differ', function () {
    DeceasedRecord::factory()->create([
        'first_name' => 'Pedro',
        'last_name' => 'Garcia',
        'date_of_birth' => '1955-08-20',
        'date_of_death' => '2018-11-30',
        'applicant_id' => Applicant::factory()->create(['contact_number' => '09123456789'])->id,
    ]);

    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Pedro',
        'Garcia',
        '1960-02-14',
        '2021-07-04'
    );

    expect($duplicate)->toBeNull();
});

it('allows creation when dates match but name differs', function () {
    DeceasedRecord::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Reyes',
        'date_of_birth' => '1970-03-22',
        'date_of_death' => '2022-09-15',
        'applicant_id' => Applicant::factory()->create(['contact_number' => '09123456789'])->id,
    ]);

    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Liza',
        'Reyes',
        '1970-03-22',
        '2022-09-15'
    );

    expect($duplicate)->toBeNull();
});

it('returns null when no records exist', function () {
    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Jose',
        'Rizal',
        '1861-06-19',
        '1896-12-30'
    );

    expect($duplicate)->toBeNull();
});

it('handles null dates in duplicate check', function () {
    DeceasedRecord::factory()->create([
        'first_name' => 'Crisostomo',
        'last_name' => 'Ibarra',
        'date_of_birth' => null,
        'date_of_death' => null,
        'applicant_id' => Applicant::factory()->create(['contact_number' => '09123456789'])->id,
    ]);

    $duplicate = $this->normalizer->findDuplicateDeceased(
        'Crisostomo',
        'Ibarra',
        null,
        null
    );

    expect($duplicate)->not->toBeNull()
        ->and($duplicate->first_name)->toBe('Crisostomo');
});
