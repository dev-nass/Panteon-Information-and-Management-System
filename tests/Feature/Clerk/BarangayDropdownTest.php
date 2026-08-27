<?php

use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);
});

it('returns the barangay list from the API endpoint', function () {
    actingAs($this->clerk)
        ->getJson(route('api.barangays'))
        ->assertSuccessful()
        ->assertJsonStructure([
            '*' => ['code', 'name'],
        ]);
});

it('contains all 75 Dasma barangays', function () {
    $response = actingAs($this->clerk)
        ->getJson(route('api.barangays'))
        ->assertSuccessful();

    $barangays = $response->json();

    expect($barangays)->toHaveCount(75);

    $names = array_column($barangays, 'name');

    expect($names)->toContain('Paliparan I')
        ->and($names)->toContain('Salawag')
        ->and($names)->toContain('Burol')
        ->and($names)->toContain('Victoria Reyes')
        ->and($names)->toContain('Zone I');
});

it('serves barangays as a publicly accessible JSON file', function () {
    $response = $this->getJson(route('api.barangays'))
        ->assertSuccessful()
        ->assertHeader('content-type', 'application/json');

    $data = $response->json();

    expect($data)->toBeArray()
        ->and($data)->not->toBeEmpty();
});
