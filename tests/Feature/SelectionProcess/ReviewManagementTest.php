<?php

namespace Tests\Feature\SelectionProcess;

use App\Domain\Review\Notifications\ReviewAssignmentsNotification;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'projects.manage', 'guard_name' => 'web']);
});

test('it can notify a single reviewer', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create(['phase' => SelectionProcessPhases::REVIEW]);
    $reviewer = User::factory()->create();
    $reviewer->assignRole('reviewer');

    $project = Project::factory()->create(['selection_process_id' => $selection->id]);
    $assignment = ReviewAssignment::factory()->create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
    ]);
    Review::factory()->create([
        'review_assignment_id' => $assignment->id,
        'status' => ReviewStatus::DRAFT,
    ]);

    $response = $this->actingAs($user)
        ->post(route('selection.reviews.notify-reviewer', [$selection, $reviewer]));

    $response->assertRedirect();
    Notification::assertSentTo($reviewer, ReviewAssignmentsNotification::class);
});

test('it can notify all reviewers with pending reviews', function () {
    Notification::fake();

    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create(['phase' => SelectionProcessPhases::REVIEW]);

    $reviewer1 = User::factory()->create();
    $reviewer1->assignRole('reviewer');
    $project1 = Project::factory()->create(['selection_process_id' => $selection->id]);
    $assignment1 = ReviewAssignment::factory()->create(['project_id' => $project1->id, 'user_id' => $reviewer1->id]);
    Review::factory()->create(['review_assignment_id' => $assignment1->id, 'status' => ReviewStatus::DRAFT]);

    $reviewer2 = User::factory()->create();
    $reviewer2->assignRole('reviewer');
    $project2 = Project::factory()->create(['selection_process_id' => $selection->id]);
    $assignment2 = ReviewAssignment::factory()->create(['project_id' => $project2->id, 'user_id' => $reviewer2->id]);
    Review::factory()->create(['review_assignment_id' => $assignment2->id, 'status' => ReviewStatus::SUBMITTED]);

    $response = $this->actingAs($user)
        ->post(route('selection.reviews.notify-all', $selection));

    $response->assertRedirect();
    Notification::assertSentTo($reviewer1, ReviewAssignmentsNotification::class);
    Notification::assertNotSentTo($reviewer2, ReviewAssignmentsNotification::class);
});

test('it can remove all reviews of a selection process', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create(['phase' => SelectionProcessPhases::REVIEW]);
    $project = Project::factory()->create(['selection_process_id' => $selection->id]);
    $assignment = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    Review::factory()->create(['review_assignment_id' => $assignment->id]);

    $this->assertDatabaseCount('reviews', 1);

    $response = $this->actingAs($user)
        ->delete(route('selection.reviews.destroy-all', $selection));

    $response->assertRedirect();
    $this->assertDatabaseCount('reviews', 0);
});

test('it can finalize review phase only when all reviews are submitted', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create(['phase' => SelectionProcessPhases::REVIEW]);
    $project = Project::factory()->create(['selection_process_id' => $selection->id]);
    $assignment = ReviewAssignment::factory()->create(['project_id' => $project->id]);
    $review = Review::factory()->create([
        'review_assignment_id' => $assignment->id,
        'status' => ReviewStatus::DRAFT,
    ]);

    // Try to finalize with pending review
    $response = $this->actingAs($user)
        ->post(route('selection.finalize', $selection));

    $response->assertSessionHasErrors('error');
    $this->assertEquals(SelectionProcessPhases::REVIEW, $selection->fresh()->phase);

    // Submit the review
    $review->update(['status' => ReviewStatus::SUBMITTED]);

    // Try again
    $response = $this->actingAs($user)
        ->post(route('selection.finalize', $selection));

    $response->assertRedirect();
    $this->assertEquals(SelectionProcessPhases::WRITTEN_EXAM, $selection->fresh()->phase);
});
