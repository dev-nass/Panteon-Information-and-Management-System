<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);

    $this->user = User::factory()->create([
        'first_name' => 'John',
        'middle_name' => 'Quincy',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'contact_number' => '09123456789',
        'role' => 'clerk',
        'email_verified_at' => now(),
    ]);
});

it('requires authentication to view profile settings', function () {
    $this->get(route('settings.profile.index'))
        ->assertRedirect(route('login'));
});

it('renders profile view with user auth data including email', function () {
    actingAs($this->user)
        ->get(route('settings.profile.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Settings/ProfileView')
            ->where('auth.user.email', 'john.doe@example.com')
            ->where('auth.user.first_name', 'John')
            ->where('auth.user.last_name', 'Doe')
            ->where('auth.user.role', 'clerk')
        );
});

it('allows updating profile names and contact number', function () {
    actingAs($this->user)
        ->post(route('settings.profile.update'), [
            'first_name' => 'Jonathan',
            'middle_name' => 'Q',
            'last_name' => 'Smith',
            'contact_number' => '09987654321',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->user->refresh();

    expect($this->user->first_name)->toBe('Jonathan')
        ->and($this->user->middle_name)->toBe('Q')
        ->and($this->user->last_name)->toBe('Smith')
        ->and($this->user->contact_number)->toBe('09987654321')
        ->and($this->user->email)->toBe('john.doe@example.com');
});

it('does not allow changing email through profile update', function () {
    actingAs($this->user)
        ->post(route('settings.profile.update'), [
            'first_name' => 'John',
            'middle_name' => 'Quincy',
            'last_name' => 'Doe',
            'contact_number' => '09123456789',
            'email' => 'hacked.email@example.com',
        ])
        ->assertRedirect();

    $this->user->refresh();

    expect($this->user->email)->toBe('john.doe@example.com');
});
