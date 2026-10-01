<?php

use App\Models\Cluster;
use App\Models\Lot;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

const CLUSTER_DUPLICATE_MESSAGE = 'A cluster with this name and type already exists in this phase.';

const LOT_DUPLICATE_MESSAGE = 'A lot with this row and column already exists in this cluster.';

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);

    if (! Schema::hasTable('phases')) {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('phase_name');
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('clusters')) {
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('phase_id');
            $table->string('cluster_name');
            $table->string('cluster_type');
            $table->bigInteger('total_capacity')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('lots')) {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cluster_id');
            $table->string('column');
            $table->string('row');
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
    $this->otherPhase = Phase::create(['phase_name' => 'Phase B']);

    $this->cluster = Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => 'Cluster A',
        'cluster_type' => 'apartment',
        'total_capacity' => 10,
    ]);

    $this->lot = Lot::create([
        'cluster_id' => $this->cluster->id,
        'column' => '1',
        'row' => 'A',
    ]);
});

it('rejects renaming a cluster to a name and type already used in the same phase', function () {
    Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => 'Cluster B',
        'cluster_type' => 'apartment',
        'total_capacity' => 10,
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.cluster', Cluster::where('cluster_name', 'Cluster B')->first()), [
            'name' => 'Cluster A',
            'type' => 'apartment',
            'total_capacity' => 10,
        ])
        ->assertSessionHasErrors(['name' => CLUSTER_DUPLICATE_MESSAGE]);

    expect($this->cluster->fresh()->cluster_name)->toBe('Cluster A');
});

it('allows a cluster name to be reused in another phase', function () {
    $otherCluster = Cluster::create([
        'phase_id' => $this->otherPhase->id,
        'cluster_name' => 'Cluster B',
        'cluster_type' => 'apartment',
        'total_capacity' => 10,
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.cluster', $otherCluster), [
            'name' => 'Cluster A',
            'type' => 'apartment',
            'total_capacity' => 10,
        ])
        ->assertSessionDoesntHaveErrors('name');

    expect($otherCluster->fresh()->cluster_name)->toBe('Cluster A');
});

it('allows a cluster name to be reused in the same phase with a different type', function () {
    $otherCluster = Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => 'Cluster B',
        'cluster_type' => 'underground',
        'total_capacity' => 10,
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.cluster', $otherCluster), [
            'name' => 'Cluster A',
            'type' => 'underground',
            'total_capacity' => 10,
        ])
        ->assertSessionDoesntHaveErrors('name');

    expect($otherCluster->fresh()->cluster_name)->toBe('Cluster A');
});

it('does not treat the current cluster name as a duplicate on update', function () {
    actingAs($this->admin)
        ->put(route('admin.lot_management.update.cluster', $this->cluster), [
            'name' => 'Cluster A',
            'type' => 'apartment',
            'total_capacity' => 15,
        ])
        ->assertSessionDoesntHaveErrors('name');

    expect($this->cluster->fresh()->total_capacity)->toBe(15);
});

it('rejects moving a lot to a row and column already used in the same cluster', function () {
    Lot::create([
        'cluster_id' => $this->cluster->id,
        'column' => '2',
        'row' => 'A',
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.lot', $this->lot), [
            'column' => '2',
            'row' => 'A',
        ])
        ->assertSessionHasErrors(['row' => LOT_DUPLICATE_MESSAGE, 'column' => LOT_DUPLICATE_MESSAGE]);

    expect($this->lot->fresh()->only(['column', 'row']))->toBe(['column' => '1', 'row' => 'A']);
});

it('rejects a duplicate lot regardless of the case of the row letter', function () {
    Lot::create([
        'cluster_id' => $this->cluster->id,
        'column' => '2',
        'row' => 'A',
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.lot', $this->lot), [
            'column' => '2',
            'row' => 'a',
        ])
        ->assertSessionHasErrors(['row' => LOT_DUPLICATE_MESSAGE, 'column' => LOT_DUPLICATE_MESSAGE]);
});

it('allows the same row and column in another cluster', function () {
    $otherCluster = Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => 'Cluster B',
        'cluster_type' => 'apartment',
        'total_capacity' => 10,
    ]);

    $otherLot = Lot::create([
        'cluster_id' => $otherCluster->id,
        'column' => '1',
        'row' => 'A',
    ]);

    actingAs($this->admin)
        ->put(route('admin.lot_management.update.lot', $otherLot), [
            'column' => '2',
            'row' => 'b',
        ])
        ->assertSessionDoesntHaveErrors(['row', 'column']);

    expect($otherLot->fresh()->only(['column', 'row']))->toBe(['column' => '2', 'row' => 'B']);
});

it('does not treat the current row and column as a duplicate on update', function () {
    actingAs($this->admin)
        ->put(route('admin.lot_management.update.lot', $this->lot), [
            'column' => '1',
            'row' => 'A',
        ])
        ->assertSessionDoesntHaveErrors(['row', 'column']);

    expect($this->lot->fresh()->only(['column', 'row']))->toBe(['column' => '1', 'row' => 'A']);
});

it('rejects creating a lot whose row and column already exist in the cluster', function () {
    actingAs($this->admin)
        ->post(route('admin.lot_management.store.lot'), [
            'cluster_id' => $this->cluster->id,
            'column' => '1',
            'row' => 'a',
            'status' => 'available',
            'coordinates' => json_encode(['type' => 'Point', 'coordinates' => [0, 0]]),
        ])
        ->assertSessionHasErrors(['row' => LOT_DUPLICATE_MESSAGE, 'column' => LOT_DUPLICATE_MESSAGE]);

    expect(Lot::count())->toBe(1);
});

it('rejects a bulk lot batch that repeats an existing row and column in a different case', function () {
    actingAs($this->admin)
        ->post(route('admin.lot_management.store.bulk_lot'), [
            'cluster_id' => $this->cluster->id,
            'lots' => [
                ['column' => '2', 'row' => 'A', 'coordinates' => json_encode(['type' => 'Point', 'coordinates' => [0, 0]])],
                ['column' => '1', 'row' => 'a', 'coordinates' => json_encode(['type' => 'Point', 'coordinates' => [0, 0]])],
            ],
        ])
        ->assertSessionHasErrors('lots');

    expect(Lot::count())->toBe(1);
});
