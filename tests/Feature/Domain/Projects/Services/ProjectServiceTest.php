<?php

use App\Domain\Projects\Services\ProjectService;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewScore;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it calculates the rounded average score of reviews', function () {
    $project = Project::factory()->create([
        'stage' => ProjectStage::IMPORTED,
    ]);

    // Create 3 reviews with scores: 4, 3, 2. Average is 3.
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::APPROVED]); // 4

    $assignment2 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment2->id, 'score' => ReviewScore::APPROVED_WITH_RESERVATIONS]); // 3

    $assignment3 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment3->id, 'score' => ReviewScore::INDICATION_TO_DISAPPROVAL]); // 2

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    expect($project->fresh()->review_score)->toBe(ReviewScore::APPROVED_WITH_RESERVATIONS);
});

test('it rounds the average score correctly (ceil/floor)', function () {
    $project = Project::factory()->create();

    // Scores: 4 and 3. Average 3.5. Round should be 4 (standard PHP round).
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::APPROVED]); // 4

    $assignment2 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment2->id, 'score' => ReviewScore::APPROVED_WITH_RESERVATIONS]); // 3

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    expect($project->fresh()->review_score->value)->toBe(4);
});

test('it handles non-integer results and updates stage to rejected if score is low', function () {
    $project = Project::factory()->create(['stage' => ProjectStage::IMPORTED]);

    // Score: 1. Average 1.
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::DISAPPROVED]); // 1

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    $project->refresh();
    expect($project->review_score)->toBe(ReviewScore::DISAPPROVED);
    expect($project->stage)->toBe(ProjectStage::REJECTED)
        ->and($project->rejected_on_stage)->toBe(ProjectStage::IMPORTED);
});
