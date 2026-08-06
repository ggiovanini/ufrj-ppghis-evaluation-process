<?php

use App\Domain\Review\Notifications\ReviewAssignmentsNotification;
use App\Domain\Review\Services\ReviewService;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('it can notify reviewers about their assignments', function () {
    Notification::fake();

    $selection = SelectionProcess::factory()->create();
    $reviewer1 = User::factory()->create();
    $reviewer2 = User::factory()->create();

    $project1 = Project::factory()->create(['selection_process_id' => $selection->id]);
    $project2 = Project::factory()->create(['selection_process_id' => $selection->id]);
    $project3 = Project::factory()->create(['selection_process_id' => $selection->id]);

    // Reviewer 1 gets Project 1 and 2
    $project1->reviewAssignments()->create(['user_id' => $reviewer1->id, 'chosen_by_candidate' => false]);
    $project2->reviewAssignments()->create(['user_id' => $reviewer1->id, 'chosen_by_candidate' => false]);

    // Reviewer 2 gets Project 3
    $project3->reviewAssignments()->create(['user_id' => $reviewer2->id, 'chosen_by_candidate' => false]);

    $service = new ReviewService($selection);
    $service->notifyReviewers();

    Notification::assertSentTo(
        $reviewer1,
        ReviewAssignmentsNotification::class,
        function ($notification) use ($project1, $project2) {
            return $notification->projects->pluck('id')->contains($project1->id) &&
                   $notification->projects->pluck('id')->contains($project2->id) &&
                   $notification->projects->count() === 2;
        }
    );

    Notification::assertSentTo(
        $reviewer2,
        ReviewAssignmentsNotification::class,
        function ($notification) use ($project3) {
            return $notification->projects->pluck('id')->contains($project3->id) &&
                   $notification->projects->count() === 1;
        }
    );
});
