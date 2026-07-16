<?php

use App\Domain\Shared\Types\UserRoles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    Permission::firstOrCreate(['name' => 'users.manage']);
});

test('only users with users.manage permission can access team creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('team.create'))
        ->assertForbidden();

    $user->givePermissionTo('users.manage');

    $this->actingAs($user)
        ->get(route('team.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/Create')
            ->has('roles')
        );
});

test('a new team member can be created', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    // Ensure roles exist in DB for Spatie
    foreach (UserRoles::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    $response = $this->actingAs($user)
        ->post(route('team.store'), [
            'name' => 'New Member',
            'email' => 'new@example.com',
            'password' => 'password123',
            'roles' => [UserRoles::ADMIN->value],
        ]);

    $newUser = User::where('email', 'new@example.com')->first();
    expect($newUser)->not->toBeNull()
        ->and($newUser->name)->toBe('New Member')
        ->and($newUser->hasRole(UserRoles::ADMIN->value))->toBeTrue();

    $response->assertRedirect();
});

test('team members list can be viewed', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    User::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('team.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/List')
            ->has('users.data', 4) // the creator + 3 new
        );
});

test('team members can be filtered by role', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    Role::firstOrCreate(['name' => UserRoles::ADMIN->value]);
    Role::firstOrCreate(['name' => UserRoles::REVIEWER->value]);

    $admin = User::factory()->create();
    $admin->assignRole(UserRoles::ADMIN->value);

    $reviewer = User::factory()->create();
    $reviewer->assignRole(UserRoles::REVIEWER->value);

    $this->actingAs($user)
        ->get(route('team.role', ['role' => UserRoles::ADMIN->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/List')
            ->has('users.data', 1)
            ->where('users.data.0.email', $admin->email)
        );
});

test('team member details can be viewed', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    $member = User::factory()->create();

    $this->actingAs($user)
        ->get(route('team.show', $member))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/Show')
            ->has('user.data')
            ->has('stats.to_evaluate')
            ->has('stats.evaluated')
            ->has('stats.written_exams')
            ->has('stats.committee_evaluations')
        );
});

test('team member can be deleted', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    $member = User::factory()->create();

    $this->actingAs($user)
        ->delete(route('team.delete', $member))
        ->assertRedirect(route('team.index'));

    expect(User::find($member->id))->toBeNull();
});

test('team member edit page can be accessed', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    $member = User::factory()->create();

    $this->actingAs($user)
        ->get(route('team.edit', $member))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/Edit')
            ->has('user')
            ->has('roles')
        );
});

test('team member can be updated', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    foreach (UserRoles::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    $member = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);
    $member->assignRole(UserRoles::REVIEWER->value);

    $this->actingAs($user)
        ->patch(route('team.update', $member), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'roles' => [UserRoles::ADMIN->value],
        ])
        ->assertRedirect(route('team.edit', $member));

    $member->refresh();
    expect($member->name)->toBe('Updated Name')
        ->and($member->email)->toBe('updated@example.com')
        ->and($member->hasRole(UserRoles::ADMIN->value))->toBeTrue()
        ->and($member->hasRole(UserRoles::REVIEWER->value))->toBeFalse();
});

test('team list page shows pagination', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    User::factory()->count(20)->create();

    $this->actingAs($user)
        ->get(route('team.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('team/List')
            ->has('users.data')
            ->has('users.meta.links')
        );
});

test('team member can be created with roles as comma separated string', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.manage');

    foreach (UserRoles::cases() as $role) {
        Role::firstOrCreate(['name' => $role->value]);
    }

    $rolesString = UserRoles::ADMIN->value.','.UserRoles::REVIEWER->value;

    $this->actingAs($user)
        ->post(route('team.store'), [
            'name' => 'String Member',
            'email' => 'string@example.com',
            'password' => 'password123',
            'roles' => $rolesString,
        ])
        ->assertRedirect();

    $newUser = User::where('email', 'string@example.com')->first();
    expect($newUser->hasRole(UserRoles::ADMIN->value))->toBeTrue()
        ->and($newUser->hasRole(UserRoles::REVIEWER->value))->toBeTrue();
});

test('team members can be searched by name or email', function () {
    $user = User::factory()->create(['name' => 'Admin User']);
    $user->givePermissionTo('users.manage');

    User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);
    User::factory()->create(['name' => 'Jane Smith', 'email' => 'jane@example.com']);

    $this->actingAs($user)
        ->get(route('team.index', ['search' => 'John']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'John Doe')
        );

    $this->actingAs($user)
        ->get(route('team.index', ['search' => 'jane@example.com']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Jane Smith')
        );
});

test('team members can be sorted by name and email', function () {
    // We clear users to have predictable order
    User::query()->delete();

    $user = User::factory()->create(['name' => 'Zebra Admin']);
    $user->givePermissionTo('users.manage');

    User::factory()->create(['name' => 'Charlie', 'email' => 'charlie@example.com']);
    User::factory()->create(['name' => 'Alpha', 'email' => 'alpha@example.com']);
    User::factory()->create(['name' => 'Bravo', 'email' => 'bravo@example.com']);

    // Sort by name ASC
    $this->actingAs($user)
        ->get(route('team.index', ['sort' => 'name', 'direction' => 'asc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.name', 'Alpha')
            ->where('users.data.1.name', 'Bravo')
            ->where('users.data.2.name', 'Charlie')
        );

    // Sort by name DESC
    $this->actingAs($user)
        ->get(route('team.index', ['sort' => 'name', 'direction' => 'desc']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('users.data.0.name', 'Zebra Admin')
            ->where('users.data.1.name', 'Charlie')
            ->where('users.data.2.name', 'Bravo')
        );
});
