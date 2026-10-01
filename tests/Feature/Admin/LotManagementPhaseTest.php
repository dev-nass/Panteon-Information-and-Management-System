<?php

use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);

    if (! Schema::hasTable('phases')) {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('phase_name');
            $table->timestamps();
        });
    }

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123478901',
        'role' => 'admin',
    ]);

    $this->phase = Phase::create(['phase_name' => 'Phase A']);

    $this->coordinates = json_encode([
        'type' => 'Polygon',
        'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]],
    ]);
});

it('rejects creating a phase with a duplicate name', function () {
    actingAs($this->admin)
        ->post(route('admin.lot_management.store.phase'), [
            'name' => 'Phase A',
            'coordinates' => $this->coordinates,
        ])
        ->assertSessionHasErrors(['name' => 'A phase with this name already exists.']);

    expect(Phase::count())->toBe(1);
});

it('does not flag a new phase name as a duplicate', function () {
    actingAs($this->admin)
        ->post(route('admin.lot_management.store.phase'), [
            'name' => 'Phase B',
            'coordinates' => $this->coordinates,
        ])
        ->assertSessionDoesntHaveErrors('name');
});

it('rejects renaming a phase to another existing phase name', function () {
    Phase::create(['phase_name' => 'Phase B']);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.phase', $this->phase), [
            'name' => 'Phase B',
            'coordinates' => $this->coordinates,
        ])
        ->assertSessionHasErrors(['name' => 'A phase with this name already exists.']);

    expect($this->phase->fresh()->phase_name)->toBe('Phase A');
});

it('does not treat the current phase name as a duplicate on update', function () {
    actingAs($this->admin)
        ->put(route('admin.lot_management.update.phase', $this->phase), [
            'name' => 'Phase A',
        ])
        ->assertSessionDoesntHaveErrors('name');

    expect($this->phase->fresh()->phase_name)->toBe('Phase A');
});
