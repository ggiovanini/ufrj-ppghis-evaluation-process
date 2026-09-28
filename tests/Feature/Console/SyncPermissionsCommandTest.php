<?php

use App\Domain\Shared\Types\UserRoles;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('it syncs all permissions and roles successfully', function () {
    $this->artisan('permissions:sync')
        ->expectsOutput('Sincronizando permissões do sistema...')
        ->expectsOutput('Permissões e papéis sincronizados com sucesso!')
        ->assertSuccessful();

    // Verify all standard roles exist
    expect(Role::where('name', UserRoles::ADMIN->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRoles::REVIEWER->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRoles::MASTER_COMMITTEE->value)->exists())->toBeTrue()
        ->and(Role::where('name', UserRoles::DOCTORATE_COMMITTEE->value)->exists())->toBeTrue();

    // Verify permissions exist
    expect(Permission::where('name', 'projects.view')->exists())->toBeTrue()
        ->and(Permission::where('name', 'projects.manage')->exists())->toBeTrue()
        ->and(Permission::where('name', 'committee.evaluate')->exists())->toBeTrue();
});

test('it grants newly added permissions to existing committee roles in database', function () {
    // Simulate database where master_committee and doctorate_committee exist with old permissions without projects.view
    Permission::firstOrCreate(['name' => 'projects.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'committee.evaluate', 'guard_name' => 'web']);

    $masterRole = Role::firstOrCreate(['name' => UserRoles::MASTER_COMMITTEE->value, 'guard_name' => 'web']);
    $masterRole->syncPermissions(['committee.evaluate']);

    $doctorateRole = Role::firstOrCreate(['name' => UserRoles::DOCTORATE_COMMITTEE->value, 'guard_name' => 'web']);
    $doctorateRole->syncPermissions(['committee.evaluate']);

    expect($masterRole->fresh()->hasPermissionTo('projects.view'))->toBeFalse()
        ->and($doctorateRole->fresh()->hasPermissionTo('projects.view'))->toBeFalse();

    $this->artisan('permissions:sync')
        ->assertSuccessful();

    // After running sync, both should have projects.view
    expect($masterRole->fresh()->hasPermissionTo('projects.view'))->toBeTrue()
        ->and($doctorateRole->fresh()->hasPermissionTo('projects.view'))->toBeTrue();
});

test('it resets permission cache during execution', function () {
    // Populate permissions cache
    app(PermissionRegistrar::class)->getPermissions();

    $this->artisan('permissions:sync')
        ->assertSuccessful();

    $masterRole = Role::findByName(UserRoles::MASTER_COMMITTEE->value, 'web');
    expect($masterRole->hasPermissionTo('projects.view'))->toBeTrue();
});

test('it is idempotent when run multiple times', function () {
    $this->artisan('permissions:sync')->assertSuccessful();
    $firstPermissionsCount = Permission::count();
    $firstRolesCount = Role::count();

    $this->artisan('permissions:sync')->assertSuccessful();
    expect(Permission::count())->toBe($firstPermissionsCount)
        ->and(Role::count())->toBe($firstRolesCount);
});
