<?php

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fix missing reviews command reports without persisting when run with --dry-run', function () {
    $reviewForm = ReviewForm::factory()->create();
    $selection = SelectionProcess::factory()->create([
        'review_form_id' => $reviewForm->id,
    ]);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
    ]);

    $reviewer = User::factory()->create();
    $assignment = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
    ]);

    expect(Review::count())->toBe(0);

    $this->artisan('projects:fix-missing-reviews --dry-run')
        ->expectsOutputToContain('Dry-run concluído: 1 avaliações seriam criadas.')
        ->assertSuccessful();

    expect(Review::count())->toBe(0);
    expect($assignment->fresh()->review)->toBeNull();
});

test('fix missing reviews command creates review records in live run', function () {
    $reviewForm = ReviewForm::factory()->create();
    $selection = SelectionProcess::factory()->create([
        'review_form_id' => $reviewForm->id,
    ]);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
    ]);

    $reviewer1 = User::factory()->create();
    $reviewer2 = User::factory()->create();

    $assignment1 = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer1->id,
    ]);

    $assignment2 = ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer2->id,
    ]);

    expect(Review::count())->toBe(0);

    $this->artisan('projects:fix-missing-reviews')
        ->expectsOutputToContain('Sucesso: 2 avaliações pendentes foram criadas.')
        ->assertSuccessful();

    expect(Review::count())->toBe(2);

    $review1 = $assignment1->fresh()->review;
    expect($review1)->not->toBeNull();
    expect($review1->status)->toBe(ReviewStatus::PENDENT);
    expect($review1->review_form_id)->toBe($reviewForm->id);

    $review2 = $assignment2->fresh()->review;
    expect($review2)->not->toBeNull();
    expect($review2->status)->toBe(ReviewStatus::PENDENT);
    expect($review2->review_form_id)->toBe($reviewForm->id);
});

test('fix missing reviews command filters by selection id when option is passed', function () {
    $reviewForm = ReviewForm::factory()->create();
    $selection1 = SelectionProcess::factory()->create(['review_form_id' => $reviewForm->id]);
    $selection2 = SelectionProcess::factory()->create(['review_form_id' => $reviewForm->id]);

    $project1 = Project::factory()->create(['selection_process_id' => $selection1->id]);
    $project2 = Project::factory()->create(['selection_process_id' => $selection2->id]);

    $reviewer = User::factory()->create();

    $assignment1 = ReviewAssignment::create([
        'project_id' => $project1->id,
        'user_id' => $reviewer->id,
    ]);

    $assignment2 = ReviewAssignment::create([
        'project_id' => $project2->id,
        'user_id' => $reviewer->id,
    ]);

    $this->artisan("projects:fix-missing-reviews --selection={$selection1->id}")
        ->expectsOutputToContain('Sucesso: 1 avaliações pendentes foram criadas.')
        ->assertSuccessful();

    expect($assignment1->fresh()->review)->not->toBeNull();
    expect($assignment2->fresh()->review)->toBeNull();
});

test('fix missing reviews command outputs message when no missing reviews exist', function () {
    $this->artisan('projects:fix-missing-reviews')
        ->expectsOutput('Nenhuma atribuição sem avaliação encontrada.')
        ->assertSuccessful();
});
