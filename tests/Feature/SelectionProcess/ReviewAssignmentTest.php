<?php

use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
    $this->admin = User::factory()->create();
    Permission::firstOrCreate(['name' => 'projects.manage', 'guard_name' => 'web']);
    $this->admin->givePermissionTo('projects.manage');

    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    $this->reviewer = User::factory()->create(['name' => 'Paulo Fontes']);
    $this->reviewer->assignRole($this->reviewerRole);

    $this->selection = SelectionProcess::factory()->create();
    $this->project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'original_content' => [
            'indicacao_de_especialista_do_corpo_docente_do_ppghis_para_avaliacao_do_projeto_de_pesquisa' => 'Paulo Fontes',
        ],
    ]);
});

test('can assign a reviewer to a project', function () {
    $this->actingAs($this->admin)
        ->post(route('selection.assignments.store', $this->selection), [
            'project_id' => $this->project->id,
            'user_id' => $this->reviewer->id,
            'chosen_by_candidate' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $this->reviewer->id,
        'chosen_by_candidate' => false,
    ]);
});

test('can auto assign reviewers based on candidate indication', function () {
    $this->actingAs($this->admin)
        ->post(route('selection.assignments.auto', $this->selection))
        ->assertRedirect();

    $this->assertDatabaseHas('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $this->reviewer->id,
        'chosen_by_candidate' => true,
    ]);
});

test('can remove all assignments from a selection process', function () {
    $this->project->reviewAssignments()->create([
        'user_id' => $this->reviewer->id,
        'chosen_by_candidate' => true,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('selection.assignments.destroy-all', $this->selection))
        ->assertRedirect();

    $this->assertDatabaseEmpty('review_assignments');
});

test('can remove all assignments from a specific project', function () {
    $project2 = Project::factory()->create(['selection_process_id' => $this->selection->id]);

    $this->project->reviewAssignments()->create([
        'user_id' => $this->reviewer->id,
        'chosen_by_candidate' => true,
    ]);

    $reviewer2 = User::factory()->create();
    $project2->reviewAssignments()->create([
        'user_id' => $reviewer2->id,
        'chosen_by_candidate' => false,
    ]);

    $this->actingAs($this->admin)
        ->delete(route('selection.projects.assignments.destroy', [$this->selection, $this->project]))
        ->assertRedirect();

    $this->assertDatabaseMissing('review_assignments', [
        'project_id' => $this->project->id,
    ]);

    $this->assertDatabaseHas('review_assignments', [
        'project_id' => $project2->id,
    ]);
});

test('can delete a project from the selection process', function () {
    $this->actingAs($this->admin)
        ->delete(route('selection.projects.delete', [$this->selection, $this->project]))
        ->assertRedirect();

    $this->assertSoftDeleted('projects', [
        'id' => $this->project->id,
    ]);
});

test('can delete all projects from the selection process', function () {
    Project::factory()->count(3)->create(['selection_process_id' => $this->selection->id]);

    // Outro projeto de outro processo para garantir que não seja deletado
    $otherSelection = SelectionProcess::factory()->create();
    $otherProject = Project::factory()->create(['selection_process_id' => $otherSelection->id]);

    $this->actingAs($this->admin)
        ->delete(route('selection.projects.delete-all', $this->selection))
        ->assertRedirect();

    $this->assertSoftDeleted('projects', [
        'id' => $this->project->id,
    ]);

    $this->assertEquals(0, Project::where('selection_process_id', $this->selection->id)->count());
    $this->assertDatabaseHas('projects', ['id' => $otherProject->id, 'deleted_at' => null]);
});

test('cannot finalize distribution phase if a project has fewer than 3 reviewers', function () {
    $this->actingAs($this->admin)
        ->post(route('selection.finalize', $this->selection))
        ->assertSessionHasErrors(['error']);

    $this->assertEquals(SelectionProcessPhases::IMPORT, $this->selection->refresh()->phase);
});

test('can finalize distribution phase when all projects have at least 3 reviewers', function () {
    // Create 3 reviewers
    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $this->project->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $this->actingAs($this->admin)
        ->post(route('selection.finalize', $this->selection))
        ->assertRedirect();

    $this->assertEquals(SelectionProcessPhases::REVIEW, $this->selection->refresh()->phase);
});
