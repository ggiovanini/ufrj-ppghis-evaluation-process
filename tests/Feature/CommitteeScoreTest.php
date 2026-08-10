<?php

use App\Domain\Front\Services\FrontIntegrationService;
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

test('an administrator can insert and update a committee score', function () {
    $admin = User::factory()->create();
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $admin->givePermissionTo('projects.manage');
    $selection = SelectionProcess::factory()->create();
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
    ]);

    $this->actingAs($admin)
        ->patch(route('selection.projects.committee-score.update', [$selection, $project]), [
            'committee_score' => '6,50',
            'comments' => 'Justificativa inicial.',
        ])
        ->assertRedirect();

    expect($project->fresh()->committee_score)->toBe(650)
        ->and($project->committeeEvaluation()->first()->score)->toBe(650)
        ->and($project->committeeEvaluation()->first()->passed)->toBeFalse()
        ->and($project->committeeEvaluation()->first()->comments)->toBe('Justificativa inicial.')
        ->and($project->committeeEvaluation()->first()->user_id)->toBe($admin->id);

    $this->actingAs($admin)
        ->patch(route('selection.projects.committee-score.update', [$selection, $project]), [
            'committee_score' => '9,00',
            'comments' => 'Justificativa atualizada.',
        ])
        ->assertRedirect();

    expect($project->fresh()->committee_score)->toBe(900)
        ->and($project->committeeEvaluation()->count())->toBe(1)
        ->and($project->committeeEvaluation()->first()->score)->toBe(900)
        ->and($project->committeeEvaluation()->first()->comments)->toBe('Justificativa atualizada.')
        ->and($project->committeeEvaluation()->first()->passed)->toBeTrue()
        ->and(FrontIntegrationService::selectionProcessStats($selection)['committee_evaluated'])->toBe(1);
});

test('the committee complete all command fills pending committee scores', function () {
    $admin = User::factory()->create();
    $adminRole = Role::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
    $admin->assignRole($adminRole);
    $selection = SelectionProcess::factory()->create();
    $pendingProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'review_score' => 800,
        'committee_score' => null,
    ]);
    $completedProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'committee_score' => 750,
    ]);

    $this->artisan('committee:complete-all')->assertSuccessful();

    expect($pendingProject->fresh()->committee_score)
        ->toBeGreaterThanOrEqual(500)
        ->toBeLessThanOrEqual(1000)
        ->and($pendingProject->committeeEvaluation()->exists())->toBeTrue()
        ->and($completedProject->fresh()->committee_score)->toBe(750)
        ->and($completedProject->committeeEvaluation()->exists())->toBeFalse();
});

test('the committee complete same command fills at most ten pending scores', function () {
    $admin = User::factory()->create();
    $adminRole = Role::create(['name' => UserRoles::ADMIN->value, 'guard_name' => 'web']);
    $admin->assignRole($adminRole);
    $selection = SelectionProcess::factory()->create();
    Project::factory()->count(12)->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::COMMITTEE,
        'review_score' => 800,
        'committee_score' => null,
    ]);

    $this->artisan('committee:complete-same')->assertSuccessful();

    expect($selection->projects()->whereNotNull('committee_score')->count())->toBe(10)
        ->and($selection->projects()->whereHas('committeeEvaluation')->count())->toBe(10);
});
