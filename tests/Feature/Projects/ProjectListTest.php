<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'projects.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'projects.manage', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'committee.evaluate', 'guard_name' => 'web']);

    $adminRole = Role::firstOrCreate(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
    $adminRole->syncPermissions(['projects.view', 'projects.manage']);

    $masterRole = Role::firstOrCreate(['name' => UserRoles::MASTER_COMMITTEE->value, 'guard_name' => 'web']);
    $masterRole->syncPermissions(['projects.view', 'committee.evaluate']);

    $doctorateRole = Role::firstOrCreate(['name' => UserRoles::DOCTORATE_COMMITTEE->value, 'guard_name' => 'web']);
    $doctorateRole->syncPermissions(['projects.view', 'committee.evaluate']);

    $reviewerRole = Role::firstOrCreate(['name' => UserRoles::REVIEWER->value, 'guard_name' => 'web']);
    $reviewerRole->syncPermissions([]);
});

test('projects can be filtered by status and modality', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('projects.view');

    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::REVIEW,
    ]);
    $matchingProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::REVIEW,
        'modality' => ProjectModality::MASTER,
    ]);
    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::FINISHED,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    $this->actingAs($user)
        ->get(route('selection.projects.index', [
            'selection' => $selection,
            'status' => ProjectStage::REVIEW->value,
            'modality' => ProjectModality::MASTER->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/List')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $matchingProject->id)
            ->where('filters.status', ProjectStage::REVIEW->value)
            ->where('filters.modality', ProjectModality::MASTER->value)
        );
});

test('master committee can only view master projects from distribution phase onwards', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRoles::MASTER_COMMITTEE->value);

    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::DISTRIBUTION,
    ]);

    $masterProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::HOMOLOGATED,
        'modality' => ProjectModality::MASTER,
    ]);

    $doctorateProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::HOMOLOGATED,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    // Accessing project list should only return master project even if querying doctorate
    $this->actingAs($user)
        ->get(route('selection.projects.index', [
            'selection' => $selection,
            'modality' => ProjectModality::DOCTORATE->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/List')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $masterProject->id)
        );
});

test('doctorate committee can only view doctorate projects from distribution phase onwards', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRoles::DOCTORATE_COMMITTEE->value);

    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::REVIEW,
    ]);

    $masterProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::REVIEW,
        'modality' => ProjectModality::MASTER,
    ]);

    $doctorateProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::REVIEW,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    // Accessing project list should only return doctorate project even if querying master
    $this->actingAs($user)
        ->get(route('selection.projects.index', [
            'selection' => $selection,
            'modality' => ProjectModality::MASTER->value,
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/List')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $doctorateProject->id)
        );
});

test('committee members cannot view projects in import or homologation phase', function () {
    $masterUser = User::factory()->create();
    $masterUser->assignRole(UserRoles::MASTER_COMMITTEE->value);

    $doctorateUser = User::factory()->create();
    $doctorateUser->assignRole(UserRoles::DOCTORATE_COMMITTEE->value);

    $importSelection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::IMPORT,
    ]);

    $homologationSelection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::HOMOLOGATION,
    ]);

    $this->actingAs($masterUser)
        ->get(route('selection.projects.index', $importSelection))
        ->assertForbidden();

    $this->actingAs($masterUser)
        ->get(route('selection.projects.index', $homologationSelection))
        ->assertForbidden();

    $this->actingAs($doctorateUser)
        ->get(route('selection.projects.index', $importSelection))
        ->assertForbidden();

    $this->actingAs($doctorateUser)
        ->get(route('selection.projects.index', $homologationSelection))
        ->assertForbidden();
});

test('committee members cannot perform admin actions on projects', function () {
    $user = User::factory()->create();
    $user->assignRole(UserRoles::MASTER_COMMITTEE->value);

    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::DISTRIBUTION,
    ]);

    $this->actingAs($user)
        ->delete(route('selection.projects.delete-all', $selection))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('selection.projects.homologation.report', $selection))
        ->assertForbidden();
});

test('admin users can view all modalities across all phases without restrictions', function () {
    $admin = User::factory()->create();
    $admin->assignRole(UserRoles::ADMIN->value);

    $importSelection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::IMPORT,
    ]);

    $masterProject = Project::factory()->create([
        'selection_process_id' => $importSelection->id,
        'stage' => ProjectStage::IMPORTED,
        'modality' => ProjectModality::MASTER,
    ]);

    $doctorateProject = Project::factory()->create([
        'selection_process_id' => $importSelection->id,
        'stage' => ProjectStage::IMPORTED,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    $this->actingAs($admin)
        ->get(route('selection.projects.index', $importSelection))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/List')
            ->has('projects.data', 2)
        );
});
