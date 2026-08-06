<?php

use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    Permission::firstOrCreate(['name' => 'projects.manage', 'guard_name' => 'web']);
    $this->admin->givePermissionTo('projects.manage');

    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);

    $this->selection = SelectionProcess::factory()->create();
});

test('auto assign complete distributes reviewers evenly', function () {
    // 4 projetos, cada um precisa de 3 avaliadores. Total de 12 atribuições.
    $projects = Project::factory(4)->create(['selection_process_id' => $this->selection->id]);

    // 6 avaliadores. 12 / 6 = 2 projetos para cada avaliador se for perfeitamente distribuído.
    $reviewers = User::factory(6)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
    }

    $this->actingAs($this->admin)
        ->post(route('selection.assignments.auto.complete', $this->selection))
        ->assertRedirect();

    // Cada projeto deve ter exatamente 3 avaliadores
    foreach ($projects as $project) {
        $this->assertEquals(3, $project->reviewAssignments()->count());
    }

    // Cada avaliador deve ter exatamente 2 projetos
    foreach ($reviewers as $reviewer) {
        $this->assertEquals(2, $reviewer->reviewAssignments()->count());
    }
});

test('auto assign complete handles projects that already have some reviewers', function () {
    $projects = Project::factory(2)->create(['selection_process_id' => $this->selection->id]);
    $reviewers = User::factory(4)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
    }

    // Atribui manualmente 1 avaliador ao primeiro projeto
    $projects[0]->reviewAssignments()->create([
        'user_id' => $reviewers[0]->id,
        'chosen_by_candidate' => false,
    ]);

    // Total de atribuições necessárias: (3 - 1) + (3 - 0) = 5
    // 4 avaliadores. 5 atribuições. Alguns terão 1, outros 2.
    // Avaliador 0 já tem 1 projeto.

    $this->actingAs($this->admin)
        ->post(route('selection.assignments.auto.complete', $this->selection))
        ->assertRedirect();

    foreach ($projects as $project) {
        $this->assertEquals(3, $project->reviewAssignments()->count());
    }

    $totalAssignments = 0;
    foreach ($reviewers as $reviewer) {
        $count = $reviewer->reviewAssignments()->count();
        $totalAssignments += $count;
        $this->assertLessThanOrEqual(2, $count);
        $this->assertGreaterThanOrEqual(1, $count);
    }
    $this->assertEquals(6, $totalAssignments);
});

test('auto assign complete ensures at least one reviewer is marked as chosen by candidate', function () {
    $project = Project::factory()->create(['selection_process_id' => $this->selection->id]);
    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
    }

    $this->actingAs($this->admin)
        ->post(route('selection.assignments.auto.complete', $this->selection))
        ->assertRedirect();

    $this->assertEquals(1, $project->reviewAssignments()->where('chosen_by_candidate', true)->count());
});

test('auto assign complete does not mark another if one is already chosen by candidate', function () {
    $project = Project::factory()->create(['selection_process_id' => $this->selection->id]);
    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
    }

    // Pré-atribui um como chosen_by_candidate
    $project->reviewAssignments()->create([
        'user_id' => $reviewers[0]->id,
        'chosen_by_candidate' => true,
    ]);

    $this->actingAs($this->admin)
        ->post(route('selection.assignments.auto.complete', $this->selection))
        ->assertRedirect();

    // Deve continuar sendo apenas 1
    $this->assertEquals(1, $project->reviewAssignments()->where('chosen_by_candidate', true)->count());
    $this->assertEquals(3, $project->reviewAssignments()->count());
});
