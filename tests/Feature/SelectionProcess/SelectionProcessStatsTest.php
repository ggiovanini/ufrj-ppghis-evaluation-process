<?php

namespace Tests\Feature\SelectionProcess;

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Review\Types\ReviewStatus;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SelectionProcessStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_selection_process_stats_are_calculated_correctly()
    {
        $selection = SelectionProcess::factory()->create();

        // Create 5 projects
        $projects = Project::factory()->count(5)->create(['selection_process_id' => $selection->id]);

        // Project 1: 3 assignments, all 3 reviews submitted
        $p1 = $projects[0];
        $this->createReviewsForProject($p1, 3, 3);

        // Project 2: 3 assignments, 2 reviews submitted, 1 draft
        $p2 = $projects[1];
        $this->createReviewsForProject($p2, 3, 2);

        // Project 3: 3 assignments, 0 reviews submitted
        $p3 = $projects[2];
        $this->createReviewsForProject($p3, 3, 0);

        // Project 4: 2 assignments (incomplete), 2 reviews submitted
        $p4 = $projects[3];
        $this->createReviewsForProject($p4, 2, 2);

        // Project 5: 0 assignments

        $stats = FrontIntegrationService::selectionProcessStats($selection);

        // total_projects: 5
        $this->assertEquals(5, $stats['total_projects']);

        // total_assigned: projects with >= 3 assignments. p1, p2, p3. Total 3.
        $this->assertEquals(3, $stats['total_assigned']);

        // total_reviewed: Individual reviews submitted.
        // p1: 3, p2: 2, p3: 0, p4: 2. Total: 3 + 2 + 0 + 2 = 7.
        $this->assertEquals(7, $stats['total_reviewed']);

        // total_reviews: Total individual reviews assigned.
        // p1: 3, p2: 3, p3: 3, p4: 2. Total: 3 + 3 + 3 + 2 = 11.
        $this->assertEquals(11, $stats['total_reviews']);

        // total_project_reviewed: Projects with 3 submitted reviews.
        // p1: yes (3/3), p2: no (2/3), p3: no (0/3), p4: no (2/2 - but needs 3). Total: 1.
        $this->assertEquals(1, $stats['total_project_reviewed']);

        // total_project_reviews: Total projects in the process. Total: 5.
        $this->assertEquals(5, $stats['total_project_reviews']);
    }

    private function createReviewsForProject(Project $project, int $assignmentsCount, int $submittedCount)
    {
        for ($i = 0; $i < $assignmentsCount; $i++) {
            $user = User::factory()->create();
            $assignment = ReviewAssignment::factory()->create([
                'project_id' => $project->id,
                'user_id' => $user->id,
            ]);

            Review::factory()->create([
                'review_assignment_id' => $assignment->id,
                'status' => $i < $submittedCount ? ReviewStatus::SUBMITTED : ReviewStatus::DRAFT,
                'submitted_at' => $i < $submittedCount ? now() : null,
            ]);
        }
    }
}
