<?php

use App\Models\BurialRecord;
use App\Models\Cluster;
use App\Models\DeceasedRecord;
use App\Models\Lot;
use App\Models\Phase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

    Schema::create('phases', function (Blueprint $table) {
        $table->id();
        $table->string('phase_name');
        $table->timestamps();
    });

    Schema::create('clusters', function (Blueprint $table) {
        $table->id();
        $table->foreignId('phase_id');
        $table->string('cluster_name');
        $table->string('cluster_type');
        $table->bigInteger('total_capacity')->nullable();
        $table->timestamps();
    });

    Schema::create('lots', function (Blueprint $table) {
        $table->id();
        $table->foreignId('cluster_id');
        $table->string('column');
        $table->string('row');
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

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123478901',
        'role' => 'admin',
    ]);

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123478902',
        'role' => 'clerk',
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

function burialRecordForLot(Lot $lot, User $user, ?string $archivedAt = null): BurialRecord
{
    return BurialRecord::create([
        'deceased_record_id' => DeceasedRecord::factory()->create()->id,
        'lot_id' => $lot->id,
        'user_id' => $user->id,
        'archived_at' => $archivedAt,
    ]);
}

it('redirects an admin lot view details to the burial record index filtered by lot', function () {
    burialRecordForLot($this->lot, $this->clerk);

    actingAs($this->admin)
        ->get(route('admin.lot_management.show', $this->lot))
        ->assertRedirect(route('admin.burial_records.index', ['lot_id' => $this->lot->id]));
});

it('redirects a clerk lot view details to the burial record index filtered by lot', function () {
    burialRecordForLot($this->lot, $this->clerk);

    actingAs($this->clerk)
        ->get(route('clerk.lot_management.show', $this->lot))
        ->assertRedirect(route('clerk.burial_records.index', ['lot_id' => $this->lot->id]));
});

it('returns only active burial records for the requested lot', function () {
    $matchingRecord = burialRecordForLot($this->lot, $this->clerk);
    burialRecordForLot($this->otherLot, $this->clerk);
    burialRecordForLot($this->lot, $this->clerk, now()->toDateTimeString());

    actingAs($this->admin)
        ->get(route('admin.burial_records.index', ['lot_id' => $this->lot->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Shared/BurialRecords/IndexView')
            ->has('burial_records.data', 1)
            ->where('burial_records.data.0.burial.id', $matchingRecord->id)
        );
});

it('includes the lot, cluster, and phase labels for the selected lot', function () {
    burialRecordForLot($this->lot, $this->clerk);

    actingAs($this->admin)
        ->get(route('admin.burial_records.index', ['lot_id' => $this->lot->id]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected_lot.id', $this->lot->id)
            ->where('selected_lot.column', '1')
            ->where('selected_lot.row', 'A')
            ->where('selected_lot.cluster', 'Cluster A')
            ->where('selected_lot.phase', '1A')
        );
});

it('preserves the lot filter alongside search, filter, and sort parameters', function () {
    burialRecordForLot($this->lot, $this->clerk);

    actingAs($this->admin)
        ->get(route('admin.burial_records.index', [
            'lot_id' => $this->lot->id,
            'search' => 'Juan',
            'filter' => 'buried',
            'sort_field' => 'deceased_last_name',
            'sort_direction' => 'asc',
            'disposal' => 'burial',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.lot_id', (string) $this->lot->id)
            ->where('filters.search', 'Juan')
            ->where('filters.filter', 'buried')
            ->where('filters.sort_field', 'deceased_last_name')
            ->where('filters.sort_direction', 'asc')
            ->where('filters.disposal', 'burial')
            ->where('selected_lot.id', $this->lot->id)
        );
});

it('rejects an invalid lot id', function () {
    actingAs($this->admin)
        ->get(route('admin.burial_records.index', ['lot_id' => 999]))
        ->assertSessionHasErrors('lot_id');
});
