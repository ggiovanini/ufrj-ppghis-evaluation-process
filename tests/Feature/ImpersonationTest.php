<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('administrators can impersonate other users', function () {
    Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->givePermissionTo('users.manage');

    $user = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('team.impersonate', $user))
        ->assertRedirect(route('dashboard'));

    expect(Auth::id())->toBe($user->id);
    expect(session('impersonated_by'))->toBe($admin->id);
});

test('users without permission cannot impersonate', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $this->actingAs($user1)
        ->post(route('team.impersonate', $user2))
        ->assertForbidden();

    expect(Auth::id())->toBe($user1->id);
});

test('can leave impersonation', function () {
    Permission::firstOrCreate(['name' => 'users.manage', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->givePermissionTo('users.manage');

    $user = User::factory()->create();

    // Inicia personificação
    $this->actingAs($admin)
        ->post(route('team.impersonate', $user));

    // Sai da personificação
    $this->post(route('impersonate.leave'))
        ->assertRedirect(route('team.index'));

    expect(Auth::id())->toBe($admin->id);
    expect(session()->has('impersonated_by'))->toBeFalse();
});
