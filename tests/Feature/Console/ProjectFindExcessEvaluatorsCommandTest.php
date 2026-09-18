<?php

use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    $this->selection = SelectionProcess::factory()->create();
});

test('it reports when no projects with excess evaluators are found', function () {
    $project = Project::factory()->create(['selection_process_id' => $this->selection->id]);
    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $project->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $this->artisan('projects:excess-evaluators')
        ->expectsOutput('Nenhum projeto com excesso de avaliadores (>3) foi encontrado.')
        ->assertSuccessful();
});

test('it lists projects that have more than 3 evaluators', function () {
    // Projeto com 3 avaliadores (normal)
    $normalProject = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'candidate_name' => 'Candidato Normal',
    ]);
    $normalReviewers = User::factory(3)->create();
    foreach ($normalReviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $normalProject->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    // Projeto com 4 avaliadores (excesso)
    $excessProject = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'candidate_name' => 'Candidato com Excesso',
    ]);
    $excessReviewers = User::factory(4)->create();
    foreach ($excessReviewers as $index => $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $excessProject->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => $index === 0,
        ]);
    }

    $this->artisan('projects:excess-evaluators')
        ->expectsOutputToContain('Candidato com Excesso')
        ->doesntExpectOutputToContain('Candidato Normal')
        ->expectsOutput('Total de projetos encontrados com excesso: 1')
        ->assertSuccessful();
});

test('it filters excess evaluator projects by selection process', function () {
    $otherSelection = SelectionProcess::factory()->create();

    $project1 = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'candidate_name' => 'Candidato Processo 1',
    ]);
    $reviewers1 = User::factory(4)->create();
    foreach ($reviewers1 as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $project1->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $project2 = Project::factory()->create([
        'selection_process_id' => $otherSelection->id,
        'candidate_name' => 'Candidato Processo 2',
    ]);
    $reviewers2 = User::factory(4)->create();
    foreach ($reviewers2 as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $project2->reviewAssignments()->create([
            'user_id' => $reviewer->id,
            'chosen_by_candidate' => false,
        ]);
    }

    $this->artisan('projects:excess-evaluators', ['--selection' => $this->selection->id])
        ->expectsOutputToContain('Candidato Processo 1')
        ->doesntExpectOutputToContain('Candidato Processo 2')
        ->expectsOutput('Total de projetos encontrados com excesso: 1')
        ->assertSuccessful();
});
