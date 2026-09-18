<?php

use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    $this->selection = SelectionProcess::factory()->create();
    $this->project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'candidate_name' => 'Candidato Teste',
        'stage' => ProjectStage::REVIEW,
    ]);
});

test('it fails when project does not exist', function () {
    $this->artisan('projects:remove-evaluator', [
        'project' => 999999,
        'user' => 1,
        '--force' => true,
    ])
        ->expectsOutput('Projeto com ID 999999 não foi encontrado.')
        ->assertFailed();
});

test('it fails when reviewer is not assigned to the project', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole($this->reviewerRole);

    $this->artisan('projects:remove-evaluator', [
        'project' => $this->project->id,
        'user' => $reviewer->id,
        '--force' => true,
    ])
        ->expectsOutput("Avaliador com ID {$reviewer->id} não está atribuído ao projeto {$this->project->id}.")
        ->assertFailed();
});

test('it can remove an evaluator with force flag when project has excess evaluators', function () {
    $reviewers = User::factory(4)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $this->project->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $evaluatorToRemove = $reviewers->first();

    $this->artisan('projects:remove-evaluator', [
        'project' => $this->project->id,
        'user' => $evaluatorToRemove->id,
        '--force' => true,
    ])
        ->expectsOutput("Avaliador [ID {$evaluatorToRemove->id}] {$evaluatorToRemove->name} removido com sucesso do projeto [ID {$this->project->id}].")
        ->expectsOutput('Total restante de avaliadores no projeto: 3')
        ->assertSuccessful();

    $this->assertDatabaseMissing('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $evaluatorToRemove->id,
    ]);

    $this->assertCount(3, $this->project->refresh()->reviewAssignments);
    $this->assertEquals(ProjectStage::REVIEW, $this->project->stage);
});

test('it resets stage to imported if remaining evaluators is less than 3', function () {
    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $this->project->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $evaluatorToRemove = $reviewers->first();

    $this->artisan('projects:remove-evaluator', [
        'project' => $this->project->id,
        'user' => $evaluatorToRemove->id,
        '--force' => true,
    ])
        ->expectsOutput('Total restante de avaliadores no projeto: 2')
        ->assertSuccessful();

    $this->assertDatabaseMissing('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $evaluatorToRemove->id,
    ]);

    $this->assertEquals(ProjectStage::IMPORTED, $this->project->refresh()->stage);
});

test('it asks for confirmation when force flag is not passed and respects cancellation', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole($this->reviewerRole);
    $this->project->reviewAssignments()->create([
        'user_id' => $reviewer->id,
        'chosen_by_candidate' => false,
    ]);

    $this->artisan('projects:remove-evaluator', [
        'project' => $this->project->id,
        'user' => $reviewer->id,
    ])
        ->expectsConfirmation('Deseja realmente remover este avaliador do projeto?', 'no')
        ->expectsOutput('Operação cancelada pelo usuário.')
        ->assertSuccessful();

    $this->assertDatabaseHas('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $reviewer->id,
    ]);
});

test('it asks for confirmation when force flag is not passed and removes when confirmed', function () {
    $reviewer = User::factory()->create();
    $reviewer->assignRole($this->reviewerRole);
    $this->project->reviewAssignments()->create([
        'user_id' => $reviewer->id,
        'chosen_by_candidate' => false,
    ]);

    $this->artisan('projects:remove-evaluator', [
        'project' => $this->project->id,
        'user' => $reviewer->id,
    ])
        ->expectsConfirmation('Deseja realmente remover este avaliador do projeto?', 'yes')
        ->expectsOutput("Avaliador [ID {$reviewer->id}] {$reviewer->name} removido com sucesso do projeto [ID {$this->project->id}].")
        ->assertSuccessful();

    $this->assertDatabaseMissing('review_assignments', [
        'project_id' => $this->project->id,
        'user_id' => $reviewer->id,
    ]);
});
