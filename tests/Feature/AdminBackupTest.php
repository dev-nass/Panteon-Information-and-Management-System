<?php

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_13_213303_create_activity_logs_table.php']);
    $this->artisan('migrate', ['--path' => 'database/migrations/2026_08_14_214215_make_subject_columns_nullable_on_activity_logs_table.php']);

    Storage::fake('backups');

    $this->admin = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Admin',
        'contact_number' => '09123456789',
        'role' => 'admin',
    ]);

    $this->clerk = User::factory()->create([
        'first_name' => 'Test',
        'last_name' => 'Clerk',
        'contact_number' => '09123456789',
        'role' => 'clerk',
    ]);
});

it('requires authentication to access the backup index', function () {
    $this->get(route('admin.backup.index'))->assertRedirect(route('login'));
});

it('only allows admins to access backup pages', function () {
    actingAs($this->clerk)
        ->get(route('admin.backup.index'))
        ->assertForbidden();

    actingAs($this->clerk)
        ->post(route('admin.backup.store'))
        ->assertForbidden();

    actingAs($this->clerk)
        ->delete(route('admin.backup.destroy', 'test.zip'))
        ->assertForbidden();
});

it('lists existing backups', function () {
    Storage::disk('backups')->put('2026-08-14-01-30-00.zip', 'backup content');

    actingAs($this->admin)
        ->get(route('admin.backup.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Backup/IndexView')
            ->has('backups', 1)
            ->where('backups.0.filename', '2026-08-14-01-30-00.zip')
            ->where('backups.0.size', 14));
});

it('creates a backup and logs the activity', function () {
    $kernel = Mockery::mock(ConsoleKernelContract::class, function (MockInterface $mock) {
        $mock->shouldReceive('call')
            ->once()
            ->with('backup:run', ['--only-db' => true, '--no-interaction' => true])
            ->andReturn(0);
    });

    Artisan::swap($kernel);

    actingAs($this->admin)
        ->post(route('admin.backup.store'))
        ->assertRedirect(route('admin.backup.index'))
        ->assertSessionHas('success', 'Backup created successfully');

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $this->admin->id,
        'action' => 'created',
        'description' => 'Created a database backup',
        'subject_type' => null,
        'subject_id' => null,
    ]);
});

it('downloads an existing backup', function () {
    Storage::disk('backups')->put('2026-08-14-01-30-00.zip', 'backup content');

    actingAs($this->admin)
        ->get(route('admin.backup.download', '2026-08-14-01-30-00.zip'))
        ->assertSuccessful()
        ->assertHeader('content-disposition', 'attachment; filename=2026-08-14-01-30-00.zip');
});

it('does not download a missing backup', function () {
    actingAs($this->admin)
        ->get(route('admin.backup.download', 'missing.zip'))
        ->assertNotFound();
});

it('deletes a backup and logs the activity', function () {
    Storage::disk('backups')->put('2026-08-14-01-30-00.zip', 'backup content');

    actingAs($this->admin)
        ->delete(route('admin.backup.destroy', '2026-08-14-01-30-00.zip'))
        ->assertRedirect()
        ->assertSessionHas('success', 'Backup deleted successfully.');

    Storage::disk('backups')->assertMissing('2026-08-14-01-30-00.zip');

    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $this->admin->id,
        'action' => 'deleted',
        'description' => 'Deleted backup 2026-08-14-01-30-00.zip',
        'subject_type' => null,
        'subject_id' => null,
    ]);
});

it('does not delete a missing backup', function () {
    actingAs($this->admin)
        ->delete(route('admin.backup.destroy', 'missing.zip'))
        ->assertRedirect()
        ->assertSessionHas('error', 'Backup file not found.');

    expect(ActivityLog::count())->toBe(0);
});
