<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Shared\Types\UserRoles;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'committee.evaluate', 'guard_name' => 'web']);
    Role::create(['name' => UserRoles::MASTER_COMMITTEE->value, 'guard_name' => 'web'])
        ->givePermissionTo('committee.evaluate');
});

test('master committee can only see master projects in the evaluation page', function () {
    $committee = User::factory()->create();
    $committee->assignRole(UserRoles::MASTER_COMMITTEE->value);
    $selection = SelectionProcess::factory()->create();
    $masterProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::FINISHED,
        'modality' => ProjectModality::MASTER,
    ]);
    $masterProject->committeeEvaluation()->create([
        'score' => 800,
        'passed' => true,
        'comments' => 'Avaliação concluída.',
        'user_id' => $committee->id,
        'submitted_at' => now(),
    ]);
    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    $this->actingAs($committee)
        ->get(route('selection.evaluate', $selection))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('projects.data.0.id', $masterProject->id)
            ->has('projects.data', 1)
        );
});

test('committee can update the score and justification for its modality', function () {
    $committee = User::factory()->create();
    $committee->assignRole(UserRoles::MASTER_COMMITTEE->value);
    $selection = SelectionProcess::factory()->create([
        'phase' => 'COMMITTEE',
    ]);
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'modality' => ProjectModality::MASTER,
    ]);

    $this->actingAs($committee)
        ->patch(route('selection.projects.committee-score.update', [$selection, $project]), [
            'committee_score' => '8,00',
            'comments' => 'Justificativa da banca.',
        ])
        ->assertRedirect();

    expect($project->fresh()->committee_score)->toBe(800)
        ->and($project->committeeEvaluation->comments)->toBe('Justificativa da banca.')
        ->and($project->committeeEvaluation->user_id)->toBe($committee->id);
});

test('committee cannot update a project from another modality', function () {
    $committee = User::factory()->create();
    $committee->assignRole(UserRoles::MASTER_COMMITTEE->value);
    $selection = SelectionProcess::factory()->create([
        'phase' => 'COMMITTEE',
    ]);
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'modality' => ProjectModality::DOCTORATE,
    ]);

    $this->actingAs($committee)
        ->patch(route('selection.projects.committee-score.update', [$selection, $project]), [
            'committee_score' => '8,00',
            'comments' => 'Não deveria ser permitido.',
        ])
        ->assertForbidden();
});
