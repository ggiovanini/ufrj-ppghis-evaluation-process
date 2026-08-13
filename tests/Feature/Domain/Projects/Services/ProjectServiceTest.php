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

    // Create 3 reviews with scores: 10, 8, 5. Average is rounded to 7.67.
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::APPROVED]); // 10

    $assignment2 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment2->id, 'score' => ReviewScore::APPROVED_WITH_RESERVATIONS]); // 8

    $assignment3 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment3->id, 'score' => ReviewScore::INDICATION_TO_DISAPPROVAL]); // 5

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    expect($project->fresh()->review_score)->toBe(767);
});

test('it rounds the average score correctly (ceil/floor)', function () {
    $project = Project::factory()->create();

    // Scores: 10 and 8. Average 9. Round should be 900 after normalization.
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::APPROVED]); // 10

    $assignment2 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment2->id, 'score' => ReviewScore::APPROVED_WITH_RESERVATIONS]); // 8

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    expect($project->fresh()->review_score)->toBe(900);
});

test('it handles non-integer results and updates stage to rejected if score is low', function () {
    $project = Project::factory()->create(['stage' => ProjectStage::IMPORTED]);

    // Score: 3. Average 3.
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'score' => ReviewScore::DISAPPROVED]); // 3

    $service = new ProjectService($project);
    $service->calculeReviewStepScore();

    $project->refresh();
    expect($project->review_score)->toBe(300);
    expect($project->stage)->toBe(ProjectStage::REJECTED)
        ->and($project->rejected_on_stage)->toBe(ProjectStage::IMPORTED);
});
