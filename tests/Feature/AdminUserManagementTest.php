<?php

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

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123478901',
        'role' => 'clerk',
    ]);
});

it('requires authentication to access the user show page', function () {
    $this->get(route('admin.user_management.show', $this->admin->id))
        ->assertRedirect(route('login'));
});

it('only allows admins to access user management pages', function () {
    actingAs($this->clerk)
        ->get(route('admin.user_management.show', $this->admin->id))
        ->assertForbidden();
});

it('renders the show view with user_data and burial_records props', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', $this->admin->id))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/UserManagement/ShowView')
            ->has('user_data')
            ->has('burial_records')
            ->has('filters')
        );
});

it('returns 404 for the removed burial_records JSON endpoint', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', $this->admin->id).'/burial-records')
        ->assertNotFound();
});

it('accepts search filter parameters without error', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', [
            'user' => $this->admin->id,
            'search' => 'test',
            'filter' => 'all',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.search', 'test')
            ->where('filters.filter', 'all')
        );
});

it('accepts sort parameters without error', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', [
            'user' => $this->admin->id,
            'sort_field' => 'deceased_first_name',
            'sort_direction' => 'desc',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.sort_field', 'deceased_first_name')
            ->where('filters.sort_direction', 'desc')
        );
});

it('accepts disposal filter parameter without error', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', [
            'user' => $this->admin->id,
            'disposal' => 'burial',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.disposal', 'burial')
        );
});

it('defaults to id desc sort when invalid sort field provided', function () {
    actingAs($this->admin)
        ->get(route('admin.user_management.show', [
            'user' => $this->admin->id,
            'sort_field' => 'invalid_field',
            'sort_direction' => 'asc',
        ]))
        ->assertSuccessful();
});
