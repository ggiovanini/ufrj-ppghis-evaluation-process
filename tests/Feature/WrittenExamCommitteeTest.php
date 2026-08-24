<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Permission::create(['name' => 'committee.evaluate', 'guard_name' => 'web']);
    Role::create(['name' => UserRoles::MASTER_COMMITTEE->value, 'guard_name' => 'web']);
});

test('the master committee can access written exam projects during its phase', function (): void {
    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::WRITTEN_EXAM,
    ]);
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'stage' => ProjectStage::WRITTEN_EXAM,
    ]);
    $committee = User::factory()->create();
    $committee->assignRole(UserRoles::MASTER_COMMITTEE->value);
    $committee->givePermissionTo('committee.evaluate');

    $this->actingAs($committee)
        ->get(route('selection.written-exam', $selection))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('written-exam/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $project->id)
        );
});

test('the master committee can save a written exam score only during the written exam phase', function (): void {
    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::WRITTEN_EXAM,
    ]);
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'stage' => ProjectStage::WRITTEN_EXAM,
    ]);
    $committee = User::factory()->create();
    $committee->assignRole(UserRoles::MASTER_COMMITTEE->value);
    $committee->givePermissionTo('committee.evaluate');

    $this->actingAs($committee)
        ->patch(route('selection.projects.update', [$selection, $project]), [
            'written_exam_score' => '8,50',
        ])
        ->assertRedirect();

    expect($project->fresh()->written_exam_score)->toBe(850);

    $selection->update(['phase' => SelectionProcessPhases::COMMITTEE]);

    $this->actingAs($committee)
        ->patch(route('selection.projects.update', [$selection, $project]), [
            'written_exam_score' => '9,00',
        ])
        ->assertStatus(409);
});
