<?php

use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000001_create_cache_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_14_214215_make_subject_columns_nullable_on_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_05_145807_create_applicants_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_02_25_130804_create_deceased_records_table.php']);

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
            $table->foreignId('phase_id')->constrained()->cascadeOnDelete();
            $table->string('cluster_name');
            $table->string('cluster_type');
            $table->string('status')->default('available');
            $table->bigInteger('total_capacity')->nullable();
            $table->timestamps();
        });
    }

    if (! Schema::hasTable('lots')) {
        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cluster_id')->constrained()->cascadeOnDelete();
            $table->string('column');
            $table->string('row');
            $table->timestamps();
        });
    }

    $this->artisan('migrate', ['--path' => 'database/migrations/2026_03_01_075233_create_burial_records_table.php']);

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123478901',
        'role' => 'admin',
    ]);

    $mock = Mockery::mock(DashboardService::class);
    $mock->shouldReceive('getTotalStats')->andReturn([
        'total_burial_records' => 0,
        'total_lots' => 0,
        'occupied_lots' => 0,
        'available_lots' => 0,
    ]);
    $mock->shouldReceive('getDisposalStats')->andReturn([
        'burial' => 0,
        'cremation' => 0,
    ]);
    $mock->shouldReceive('getActivityData')->andReturn([
        'labels' => [],
        'values' => [],
    ]);
    $mock->shouldReceive('getAgeDistribution')->andReturn([
        'labels' => ['0-12', '13-19', '20-39', '40-59', '60-74', '75+', 'Unknown'],
        'values' => [0, 0, 0, 0, 0, 0, 0],
    ]);
    $mock->shouldReceive('getGeographicDistribution')->andReturn([
        'labels' => [],
        'values' => [],
    ]);
    $mock->shouldReceive('getFilterOptions')->andReturn([
        'barangays' => [],
    ]);
    $mock->shouldReceive('normalizeBarangay')->andReturnUsing(fn ($val) => strtolower(trim($val)));
    $this->app->instance(DashboardService::class, $mock);
});

afterEach(function () {
    Mockery::close();
});

it('renders the summary tab with expected props', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'summary']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DashboardView')
            ->has('stats')
            ->has('disposal_stats')
            ->has('activity_data')
            ->has('demographic_data')
            ->has('geographic_data')
            ->has('filter_options')
            ->has('active_filters')
        );
});

it('passes active_filters with default null values', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'summary']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('active_filters.age_range', null)
            ->where('active_filters.barangay', null)
        );
});

it('passes filter_options with barangays key', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'summary']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->has('filter_options.barangays')
        );
});

it('passes selected_year as integer', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'summary', 'year' => 2025]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected_year', 2025)
        );
});

it('renders phases tab with phase_data', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'phases']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DashboardView')
            ->has('phase_data')
        );
});

it('renders clusters tab with phases list', function () {
    actingAs($this->admin)
        ->get(route('admin.dashboard', ['tab' => 'clusters']))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/DashboardView')
            ->has('phases')
            ->has('cluster_data')
        );
});

it('normalizes barangay addresses consistently', function () {
    $service = new DashboardService;

    expect($service->normalizeBarangay('SAMPALOC IV'))->toBe('sampaloc 4');
    expect($service->normalizeBarangay('Paliparan 3'))->toBe('paliparan 3');
    expect($service->normalizeBarangay('PALIPARAN III'))->toBe('paliparan 3');
    expect($service->normalizeBarangay('  Salawag  '))->toBe('salawag');
    expect($service->normalizeBarangay('BRGY. Sampaloc II'))->toBe('sampaloc 2');
    expect($service->normalizeBarangay('Barangay Burol 1'))->toBe('burol 1');
    expect($service->normalizeBarangay('BUROL III'))->toBe('burol 3');
    expect($service->normalizeBarangay('Victoria Reyes'))->toBe('victoria reyes');
});

it('normalizes roman numerals in various cases', function () {
    $service = new DashboardService;

    expect($service->normalizeBarangay('SAMPALOC I'))->toBe('sampaloc 1');
    expect($service->normalizeBarangay('SAMPALOC II'))->toBe('sampaloc 2');
    expect($service->normalizeBarangay('SAMPALOC III'))->toBe('sampaloc 3');
    expect($service->normalizeBarangay('SAMPALOC IV'))->toBe('sampaloc 4');
    expect($service->normalizeBarangay('SAMPALOC V'))->toBe('sampaloc 5');
    expect($service->normalizeBarangay('SAMPALOC X'))->toBe('sampaloc 10');
});

it('has correct age bucket definitions', function () {
    expect(DashboardService::AGE_BUCKETS)->toHaveKeys([
        '0-12', '13-19', '20-39', '40-59', '60-74', '75+',
    ]);

    expect(DashboardService::AGE_BUCKETS['0-12'])->toBe([0, 12]);
    expect(DashboardService::AGE_BUCKETS['75+'])->toBe([75, PHP_INT_MAX]);
});
