<?php

use App\Domain\Committee\Types\FinalStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\SelectionProcess\Services\ResultService;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('it creates, updates and finalizes project results', function () {
    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::RESULTS,
    ]);
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::FINISHED,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
        'written_exam_score' => 700,
        'committee_score' => 900,
    ]);
    $project->committeeEvaluation()->create([
        'score' => 900,
        'passed' => true,
        'comments' => 'Avaliação concluída.',
        'user_id' => $user->id,
        'submitted_at' => now(),
    ]);

    $service = app(ResultService::class);
    $service->create($project);

    expect($project->fresh()->final_score)->toBe(800)
        ->and($project->finalResults()->first()->review_average)->toBe(8.0)
        ->and($project->finalResults()->first()->passed)->toBeTrue()
        ->and($project->finalResults()->first()->status)->toBe(FinalStatus::APPROVED);

    $project->update(['committee_score' => 500]);
    $service->update($project->fresh());

    expect($project->fresh()->final_score)->toBe(640)
        ->and($project->finalResults()->first()->passed)->toBeFalse()
        ->and($project->finalResults()->first()->status)->toBe(FinalStatus::REJECTED);

    $doctorateProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'stage' => ProjectStage::FINISHED,
        'modality' => ProjectModality::DOCTORATE,
        'review_score' => 800,
        'committee_score' => 900,
    ]);
    $doctorateProject->committeeEvaluation()->create([
        'score' => 900,
        'passed' => true,
        'comments' => 'Avaliação concluída.',
        'user_id' => $user->id,
        'submitted_at' => now(),
    ]);

    $service->create($doctorateProject);

    expect($doctorateProject->fresh()->final_score)->toBe(860);

    $service->finalize($selection);

    expect($selection->fresh()->phase)->toBe(SelectionProcessPhases::FINISHED);
});

test('an administrator can recalculate all final results from the results phase', function () {
    $admin = User::factory()->create();
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $admin->givePermissionTo('projects.manage');
    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::RESULTS,
    ]);
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
        'written_exam_score' => 700,
        'committee_score' => 900,
    ]);
    $project->committeeEvaluation()->create([
        'score' => 900,
        'passed' => true,
        'comments' => 'Avaliação concluída.',
        'user_id' => $admin->id,
        'submitted_at' => now(),
    ]);
    $project->finalResults()->create([
        'review_average' => 8,
        'written_exam_score' => 700,
        'committee_score' => 900,
        'final_score' => 100,
        'passed' => false,
        'status' => FinalStatus::REJECTED,
    ]);

    $this->actingAs($admin)
        ->post(route('selection.results.recalculate', $selection))
        ->assertRedirect();

    expect($project->fresh()->final_score)->toBe(800)
        ->and($project->finalResults()->first()->passed)->toBeTrue()
        ->and($project->finalResults()->first()->status)->toBe(FinalStatus::APPROVED);
});
