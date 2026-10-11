<?php

use App\Models\ActivityLog;
use App\Models\Applicant;
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
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_14_214215_make_subject_columns_nullable_on_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_09_13_000004_extend_activity_logs_action_enum_terminated.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

    if (! Schema::hasTable('phases')) {
        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('phase_name');
            $table->text('coordinates')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('clusters')) {
        Schema::create('clusters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->string('cluster_name');
            $table->string('cluster_type');
            $table->bigInteger('total_capacity')->nullable();
            $table->text('coordinates')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('lots')) {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained()->cascadeOnDelete();
            $table->string('column');
            $table->string('row');
            $table->text('coordinates')->nullable();
            $table->timestamps();
        });
    }

    if (DB::connection()->getDriverName() === 'sqlite') {
        DB::connection()->getPdo()->sqliteCreateFunction('ST_GeomFromGeoJSON', fn ($val) => $val);
    }

    $this->artisan('migrate', ['--path' => 'database/migrations/2026_03_01_075233_create_burial_records_table.php']);

    if (! Schema::hasColumn('burial_records', 'archived_at')) {
        Schema::table('burial_records', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->index();
            $table->string('archived_reason')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('archived_notes')->nullable();
        });
    }

    $this->admin = User::factory()->create([
        'first_name' => 'Admin',
        'last_name' => 'User',
        'contact_number' => '09123456789',
        'role' => 'admin',
    ]);

    $this->clerk = User::factory()->create([
        'first_name' => 'Clerk',
        'last_name' => 'User',
        'contact_number' => '09987654321',
        'role' => 'clerk',
    ]);
});

it('requires authentication to access activity log index', function () {
    $this->get(route('admin.activity_log.index'))
        ->assertRedirect(route('login'));
});

it('forbids clerks from viewing activity logs', function () {
    actingAs($this->clerk)
        ->get(route('admin.activity_log.index'))
        ->assertForbidden();
});

it('allows admins to view activity logs with inertia props', function () {
    ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'subject_type' => User::class,
        'subject_id' => $this->admin->id,
        'description' => 'Admin logged in',
        'properties' => ['snapshot' => ['name' => 'Admin User']],
        'ip_address' => '127.0.0.1',
    ]);

    actingAs($this->admin)
        ->get(route('admin.activity_log.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/ActivityLog/IndexView')
            ->has('logs.data', 1)
            ->has('stats')
            ->has('chart_data')
            ->where('logs.data.0.subject_title', 'User')
            ->where('logs.data.0.subject_exists', true)
        );
});

it('resolves subject title, url, and exists correctly for existing and deleted records', function () {
    $phase = Phase::create(['phase_name' => 'Phase 1']);
    $cluster = Cluster::create([
        'phase_id' => $phase->id,
        'cluster_name' => 'Cluster A',
        'cluster_type' => 'apartment',
        'total_capacity' => 20,
    ]);
    $lot = Lot::create([
        'cluster_id' => $cluster->id,
        'column' => '01',
        'row' => 'A',
    ]);

    $log = ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'subject_type' => Lot::class,
        'subject_id' => $lot->id,
        'description' => 'Created lot A-01',
        'properties' => ['snapshot' => ['column' => '01', 'row' => 'A']],
        'ip_address' => '127.0.0.1',
    ]);

    expect($log->subject)->toBeInstanceOf(Lot::class)
        ->and($log->subject_title)->toBe('Lot')
        ->and($log->subject_exists)->toBeTrue()
        ->and($log->subject_url)->toBe(route('admin.lot_management.show', $lot->id));

    // When the subject is deleted
    $lot->delete();

    $log->refresh();
    expect($log->subject)->toBeNull()
        ->and($log->subject_exists)->toBeFalse();
});

it('resolves subject_title and subject_url for various model types', function () {
    $burialLog = ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'updated',
        'subject_type' => BurialRecord::class,
        'subject_id' => 99,
        'description' => 'Updated burial record #99',
    ]);

    expect($burialLog->subject_title)->toBe('Burial Record')
        ->and($burialLog->subject_url)->toBe(route('admin.burial_records.show', 99));

    $userLog = ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'updated',
        'subject_type' => User::class,
        'subject_id' => $this->clerk->id,
        'description' => 'Updated clerk',
    ]);

    expect($userLog->subject_title)->toBe('User')
        ->and($userLog->subject_url)->toBe(route('admin.user_management.show', $this->clerk->id));

    $nullLog = ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'subject_type' => null,
        'subject_id' => null,
        'description' => 'Created backup',
    ]);

    expect($nullLog->subject_title)->toBe('System')
        ->and($nullLog->subject_url)->toBeNull()
        ->and($nullLog->subject_exists)->toBeFalse();
});

it('filters logs by action', function () {
    ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'description' => 'Created record',
    ]);

    ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'deleted',
        'description' => 'Deleted record',
    ]);

    actingAs($this->admin)
        ->get(route('admin.activity_log.index', ['action' => 'deleted']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.action', 'deleted')
        );
});

it('filters logs by time range', function () {
    ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'description' => 'Created today',
        'created_at' => now(),
    ]);

    $oldLog = ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'description' => 'Created 15 days ago',
    ]);
    $oldLog->timestamps = false;
    $oldLog->created_at = now()->subDays(15);
    $oldLog->save();

    actingAs($this->admin)
        ->get(route('admin.activity_log.index', ['range' => '7days']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Created today')
        );

    actingAs($this->admin)
        ->get(route('admin.activity_log.index', ['range' => 'today']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
        );
});

it('filters logs by user search', function () {
    ActivityLog::create([
        'user_id' => $this->admin->id,
        'action' => 'created',
        'description' => 'Admin action',
    ]);

    ActivityLog::create([
        'user_id' => $this->clerk->id,
        'action' => 'created',
        'description' => 'Clerk action',
    ]);

    actingAs($this->admin)
        ->get(route('admin.activity_log.index', ['user_search' => 'Clerk']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('logs.data', 1)
            ->where('logs.data.0.user.first_name', 'Clerk')
        );
});

it('logs phase creation with snapshot', function () {
    actingAs($this->admin)
        ->post(route('admin.lot_management.store.phase'), [
            'name' => 'Phase Omega',
            'coordinates' => json_encode(['type' => 'Polygon', 'coordinates' => [[[0, 0], [1, 0], [1, 1], [0, 0]]]]),
        ])
        ->assertRedirect(route('admin.lot_management.index'));

    $log = ActivityLog::where('action', 'created')->where('subject_type', Phase::class)->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toBe('Created phase Phase Omega')
        ->and($log->properties['snapshot'])->toBe(['phase_name' => 'Phase Omega']);
});

it('logs phase deletion with snapshot', function () {
    $phase = Phase::create(['phase_name' => 'Phase To Delete']);

    actingAs($this->admin)
        ->delete(route('admin.lot_management.delete.phase', $phase))
        ->assertRedirect(route('admin.lot_management.index'));

    $log = ActivityLog::where('action', 'deleted')->where('subject_type', Phase::class)->first();
    expect($log)->not->toBeNull()
        ->and($log->description)->toBe('Deleted phase Phase To Delete')
        ->and($log->properties['snapshot'])->toBe(['phase_name' => 'Phase To Delete']);
});

it('preserves deceased snapshot when burial record is deleted', function () {
    $applicant = Applicant::factory()->create([
        'first_name' => 'Ana',
        'last_name' => 'Cruz',
        'relationship' => 'Spouse',
        'contact_number' => '09123456789',
    ]);
    $deceased = DeceasedRecord::factory()->create([
        'first_name' => 'Jose',
        'middle_name' => 'P.',
        'last_name' => 'Rizal',
        'applicant_id' => $applicant->id,
        'date_of_birth' => '1861-06-19',
        'date_of_death' => '1896-12-30',
        'date_of_depository' => '1896-12-31',
        'address' => 'Calamba, Laguna',
    ]);
    $phase = Phase::create(['phase_name' => 'Phase 1']);
    $cluster = Cluster::create([
        'phase_id' => $phase->id,
        'cluster_name' => 'Cluster A',
        'cluster_type' => 'apartment',
    ]);
    $lot = Lot::create([
        'cluster_id' => $cluster->id,
        'column' => '01',
        'row' => 'A',
    ]);
    $burialRecord = BurialRecord::create([
        'deceased_record_id' => $deceased->id,
        'lot_id' => $lot->id,
        'user_id' => $this->clerk->id,
    ]);

    actingAs($this->clerk)
        ->delete(route('clerk.burial_records.destroy', $burialRecord))
        ->assertRedirect(route('clerk.burial_records.index'));

    expect(BurialRecord::find($burialRecord->id))->toBeNull();

    $log = ActivityLog::where('action', 'deleted')
        ->where('subject_type', BurialRecord::class)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toBe('Deleted burial record for Jose Rizal')
        ->and($log->properties['snapshot'])->toMatchArray([
            'deceased_name' => 'Jose P. Rizal',
            'date_of_birth' => '1861-06-19',
            'date_of_death' => '1896-12-30',
            'date_of_depository' => '1896-12-31',
            'address' => 'Calamba, Laguna',
            'plot' => 'Phase 1 / Cluster A / A-01',
            'applicant' => 'Ana Cruz',
        ])
        ->and($log->subject_exists)->toBeFalse();
});

it('logs burial record creation with full snapshot', function () {
    $phase = Phase::create(['phase_name' => 'Phase 1']);
    $cluster = Cluster::create([
        'phase_id' => $phase->id,
        'cluster_name' => 'Cluster A',
        'cluster_type' => 'apartment',
    ]);
    $lot = Lot::create([
        'cluster_id' => $cluster->id,
        'column' => '05',
        'row' => 'B',
    ]);

    actingAs($this->clerk)
        ->post(route('clerk.burial_records.store'), [
            'first_name' => 'Andres',
            'middle_name' => 'C.',
            'last_name' => 'Bonifacio',
            'birth_date' => '1863-11-30',
            'death_date' => '1897-05-10',
            'burial_date' => '1897-05-12',
            'burial_time' => '14:00',
            'address' => 'Tondo, Manila',
            'precinct_num' => '001A',
            'corpse_disposal' => 'burial',
            'applicant_first_name' => 'Gregoria',
            'applicant_last_name' => 'De Jesus',
            'applicant_contact_number' => '09123456789',
            'applicant_relationship' => 'Spouse',
            'lot_id' => $lot->id,
        ])
        ->assertRedirect();

    $log = ActivityLog::where('action', 'created')
        ->where('subject_type', BurialRecord::class)
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toBe('Created burial record for Andres Bonifacio')
        ->and($log->properties['snapshot'])->toMatchArray([
            'deceased_name' => 'Andres C. Bonifacio',
            'date_of_birth' => '1863-11-30',
            'date_of_death' => '1897-05-10',
            'address' => 'Tondo, Manila',
            'plot' => 'Phase 1 / Cluster A / B-05',
            'applicant' => 'Gregoria De Jesus',
            'relationship' => 'Spouse',
        ])
        ->and($log->subject_exists)->toBeTrue();
});
