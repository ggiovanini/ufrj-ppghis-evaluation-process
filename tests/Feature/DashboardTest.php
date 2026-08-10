<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $selection = SelectionProcess::factory()->create();
    $user = User::factory()->create(['current_selection_process_id' => $selection->id]);
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('management sees aggregate dashboard indicators', function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $selection = SelectionProcess::factory()->create();
    $user = User::factory()->create(['current_selection_process_id' => $selection->id]);
    $user->givePermissionTo('projects.manage');

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.audience', 'management')
            ->where('dashboard.modality', null)
        );
});

test('committee sees only its modality indicators', function () {
    Role::create(['name' => 'master_committee', 'guard_name' => 'web']);
    $selection = SelectionProcess::factory()->create();
    $user = User::factory()->create(['current_selection_process_id' => $selection->id]);
    $user->assignRole('master_committee');

    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
    ]);
    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('dashboard.audience', 'committee')
            ->where('dashboard.modality', 'master')
            ->where('dashboard.stats.total_projects', 1)
        );
});
