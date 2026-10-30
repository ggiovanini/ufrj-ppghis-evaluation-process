<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;
use App\Models\WrittenExam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('it can render project details page', function () {
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'projects.view', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.view');

    $selection = SelectionProcess::factory()->create();
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'João Silva',
        'title' => 'Projeto de Teste',
        'original_content' => ['campo_teste' => 'valor teste'],
    ]);

    // Adiciona uma atribuição para o usuário logado
    $assignment = ReviewAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);
    Review::factory()->create([
        'review_assignment_id' => $assignment->id,
        'status' => ReviewStatus::SUBMITTED,
        'score' => 10,
    ]);

    // Adiciona exame escrito
    WrittenExam::create([
        'project_id' => $project->id,
        'score' => 9,
        'user_id' => $user->id,
        'recorded_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('selection.projects.show', [$selection, $project]));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/Show')
        ->has('project', fn (Assert $projectData) => $projectData
            ->where('candidate_name', 'João Silva')
            ->where('title', 'Projeto de Teste')
            ->has('review_assignments')
            ->has('written_exam')
            ->where('is_evaluated', true)
            ->etc()
        )
    );
});

test('it shares audience roles and project documents on the project details page', function (string $role) {
    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'projects.view', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'committee.evaluate', 'guard_name' => 'web']);

    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::COMMITTEE,
    ]);
    $modality = $role === 'doctorate_committee'
        ? ProjectModality::DOCTORATE
        : ProjectModality::MASTER;
    $documents = [
        ['label' => 'Projeto', 'name' => 'projeto.pdf', 'filename' => 'projeto.pdf'],
        ['label' => 'Memorial', 'name' => 'memorial.pdf', 'filename' => 'memorial.pdf'],
        ['label' => 'Ficha', 'name' => 'ficha.pdf', 'filename' => 'ficha.pdf'],
    ];
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => $modality,
        'content' => ['documents' => $documents],
    ]);
    $user = User::factory()->create();
    $user->assignRole($role);

    if ($role === 'reviewer') {
        $user->givePermissionTo('projects.view');

        ReviewAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $user->id,
        ]);
    } else {
        $user->givePermissionTo('committee.evaluate');
    }

    $this->actingAs($user)
        ->get(route('selection.projects.show', [$selection, $project]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('projects/Show')
            ->where('auth.roles', [$role])
            ->where('project.content.documents', $documents)
        );
})->with([
    'master committee' => 'master_committee',
    'doctorate committee' => 'doctorate_committee',
    'reviewer' => 'reviewer',
]);
