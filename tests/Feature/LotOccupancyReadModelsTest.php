<?php

use App\Http\Controllers\Admin\GenerateReportController;
use App\Http\Resources\PhaseResource;
use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

    Schema::create('phases', function (Blueprint $table) {
        $table->id();
        $table->string('phase_name');
        $table->text('coordinates')->nullable();
        $table->timestamps();
    });

    Schema::create('clusters', function (Blueprint $table) {
        $table->id();
        $table->foreignId('phase_id');
        $table->string('cluster_name');
        $table->string('cluster_type');
        $table->bigInteger('total_capacity')->nullable();
        $table->text('coordinates')->nullable();
        $table->timestamps();
    });

    Schema::create('lots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cluster_id');
        $table->string('column');
        $table->string('row');
        $table->text('coordinates')->nullable();
        $table->timestamps();
    });

    Schema::create('burial_records', function (Blueprint $table) {
        $table->id();
        $table->foreignId('lot_id')->nullable();
        $table->foreignId('deceased_record_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->timestamp('archived_at')->nullable();
        $table->string('archived_reason')->nullable();
        $table->foreignId('archived_by')->nullable();
        $table->text('archived_notes')->nullable();
        $table->timestamps();
    });

    DB::connection()->getPdo()->sqliteCreateFunction(
        'ST_AsGeoJSON',
        fn ($coordinates) => $coordinates,
    );

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123478901',
        'role' => 'admin',
    ]);

    $this->phase = Phase::create(['phase_name' => '1A']);

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

    $this->otherLot = Lot::create([
        'cluster_id' => $this->cluster->id,
        'column' => '2',
        'row' => 'B',
    ]);
});

function readModelBurialRecord(Lot $lot, User $user, ?string $archivedAt = null): BurialRecord
{
    return BurialRecord::create([
        'deceased_record_id' => DeceasedRecord::factory()->create()->id,
        'lot_id' => $lot->id,
        'user_id' => $user->id,
        'archived_at' => $archivedAt,
    ]);
}

it('returns active burial counts and ignores archived records for lots', function () {
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->otherLot, $this->admin, now()->toDateTimeString());

    actingAs($this->admin)
        ->getJson(route('api.lot.management.lots', $this->cluster->id))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $this->lot->id,
            'status' => 'occupied',
            'burial_count' => 2,
        ])
        ->assertJsonFragment([
            'id' => $this->otherLot->id,
            'status' => 'available',
            'burial_count' => 0,
        ]);
});

it('counts distinct occupied lots and ignores archived records for clusters', function () {
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->otherLot, $this->admin, now()->toDateTimeString());

    actingAs($this->admin)
        ->getJson(route('api.lot.management.clusters', $this->phase->id))
        ->assertOk()
        ->assertJsonFragment([
            'id' => $this->cluster->id,
            'total_lots' => 2,
            'occupants' => 1,
        ]);
});

it('excludes archived records from the lot map lookup', function () {
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->lot, $this->admin, now()->toDateTimeString());

    actingAs($this->admin)
        ->getJson(route('api.lot.management.lot', ['lot_id' => $this->lot->id]))
        ->assertOk()
        ->assertJsonPath('data.0.lot.properties.status', 'occupied')
        ->assertJsonPath('data.0.lot.properties.burial_count', 1)
        ->assertJsonCount(1, 'data.0.burial_records');
});

it('excludes archived records from the cluster map lookup', function () {
    $cluster = Cluster::create([
        'phase_id' => $this->phase->id,
        'cluster_name' => 'Cluster B',
        'cluster_type' => 'apartment',
        'total_capacity' => 5,
    ]);

    $lot = Lot::create([
        'cluster_id' => $cluster->id,
        'column' => '1',
        'row' => 'A',
    ]);

    readModelBurialRecord($lot, $this->admin);
    readModelBurialRecord($lot, $this->admin, now()->toDateTimeString());

    actingAs($this->admin)
        ->getJson(route('api.lot.management.cluster', ['cluster_id' => $cluster->id]))
        ->assertOk()
        ->assertJsonPath('data.0.cluster.properties.occupied_lots', 1)
        ->assertJsonCount(1, 'data.0.lots.0.burial_records');
});

it('does not count archived records in phase occupancy totals', function () {
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->otherLot, $this->admin, now()->toDateTimeString());

    $data = (new PhaseResource($this->phase))->toArray(Request::create('/'));

    expect($data['phase']['properties'])
        ->toMatchArray([
            'total_capacity' => 2,
            'occupants' => 2,
            'occupied_lots' => 1,
            'total_clusters' => 1,
        ]);
});

it('counts distinct occupied lots in the phase availability report without negative availability', function () {
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->lot, $this->admin);
    readModelBurialRecord($this->otherLot, $this->admin);

    $controller = app(GenerateReportController::class);
    $method = new ReflectionMethod($controller, 'getPhaseAvailabilityData');
    $method->setAccessible(true);

    $phase = $method->invoke($controller)->firstWhere('phase_name', '1A');

    expect($phase)->toMatchArray([
        'total_clusters' => 1,
        'total_lots' => 2,
        'total_occupants' => 3,
        'occupied_lots' => 2,
        'available_lots' => 0,
        'occupancy_rate' => 100.0,
    ]);
});
