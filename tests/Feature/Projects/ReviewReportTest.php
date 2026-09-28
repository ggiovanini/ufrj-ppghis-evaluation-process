<?php

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Exports\ReviewReportExport;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
});

test('a project manager can download review report when all reviews are submitted and scored', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo('projects.manage');

    $reviewForm = ReviewForm::factory()->create();
    $selection = SelectionProcess::factory()->create([
        'review_form_id' => $reviewForm->id,
    ]);

    $reviewer1 = User::factory()->create(['name' => 'Avaliador Um']);
    $reviewer2 = User::factory()->create(['name' => 'Avaliador Dois']);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
        'review_score' => 100,
    ]);

    $assignment1 = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer1->id,
    ]);
    Review::create([
        'review_assignment_id' => $assignment1->id,
        'review_form_id' => $reviewForm->id,
        'status' => ReviewStatus::SUBMITTED,
        'score' => ReviewScore::APPROVED,
    ]);

    $assignment2 = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer2->id,
    ]);
    Review::create([
        'review_assignment_id' => $assignment2->id,
        'review_form_id' => $reviewForm->id,
        'status' => ReviewStatus::SUBMITTED,
        'score' => ReviewScore::APPROVED_WITH_RESERVATIONS,
    ]);

    $this->actingAs($manager)
        ->get(route('selection.projects.review.report', $selection))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=relatorio-avaliacao-'.$selection->id.'.xlsx');

    $export = new ReviewReportExport($selection);
    $rows = $export->collection();

    expect($rows)->toHaveCount(1);
    expect($rows->first()[4])->toContain('Avaliador Um (Aprovado)');
    expect($rows->first()[4])->toContain('Avaliador Dois (Aprovado com ressalvas)');
});

test('downloading review report succeeds when reviews exist but are pending with default pendent score', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo('projects.manage');

    $reviewForm = ReviewForm::factory()->create();
    $selection = SelectionProcess::factory()->create([
        'review_form_id' => $reviewForm->id,
    ]);

    $reviewer = User::factory()->create(['name' => 'Avaliador Pendente']);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
    ]);

    $assignment = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
    ]);
    Review::create([
        'review_assignment_id' => $assignment->id,
        'review_form_id' => $reviewForm->id,
        'status' => ReviewStatus::PENDENT,
        'score' => ReviewScore::PENDENT,
    ]);

    $this->actingAs($manager)
        ->get(route('selection.projects.review.report', $selection))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=relatorio-avaliacao-'.$selection->id.'.xlsx');

    $export = new ReviewReportExport($selection);
    $rows = $export->collection();

    expect($rows)->toHaveCount(1);
    expect($rows->first()[4])->toBe('Avaliador Pendente (Não avaliado)');
});

test('downloading review report succeeds when review assignment has no associated review record', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create();
    $reviewer = User::factory()->create(['name' => 'Avaliador Sem Review']);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
    ]);

    ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
    ]);

    $this->actingAs($manager)
        ->get(route('selection.projects.review.report', $selection))
        ->assertOk();

    $export = new ReviewReportExport($selection);
    $rows = $export->collection();

    expect($rows)->toHaveCount(1);
    expect($rows->first()[4])->toBe('Avaliador Sem Review (Não avaliado)');
});

test('review report handles null user relation gracefully', function () {
    $selection = SelectionProcess::factory()->create();
    $user = User::factory()->create();

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
    ]);

    $assignment = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $user->id,
    ]);

    // Force user relation to null for testing defensive fallback
    $assignment->setRelation('user', null);
    $project->setRelation('reviewAssignments', collect([$assignment]));

    $reviewers = $project->reviewAssignments->map(function ($reviewAssignment): string {
        $userName = $reviewAssignment->user?->name ?? 'N/A';
        $scoreLabel = $reviewAssignment->review?->score?->label() ?? ReviewScore::PENDENT->label();

        return "{$userName} ({$scoreLabel})";
    });

    expect($reviewers->implode(', '))->toBe('N/A (Não avaliado)');
});
