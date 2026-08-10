<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('projects can be filtered by status and modality', function () {
    Permission::create(['name' => 'projects.view', 'guard_name' => 'web']);
    Role::create(['name' => 'reviewer', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.view');

    $selection = SelectionProcess::factory()->create();
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
