<?php

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Domain\Review\Types\ReviewScore;
use App\Domain\Review\Types\ReviewStatus;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\Review;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->reviewerRole = Role::firstOrCreate(['name' => 'reviewer', 'guard_name' => 'web']);
    $this->selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::REVIEW,
    ]);
});

test('it updates stage to review for approved projects with pending reviews in review phase', function () {
    $project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'stage' => ProjectStage::IMPORTED,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
    ]);

    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $assignment = ReviewAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $reviewer->id,
        ]);
        Review::factory()->create([
            'review_assignment_id' => $assignment->id,
            'status' => ReviewStatus::PENDENT,
        ]);
    }

    $this->artisan('projects:sync-stages', [
        '--selection' => $this->selection->id,
    ])
        ->expectsOutput('Sucesso: 1 projeto(s) sincronizados com sucesso.')
        ->assertSuccessful();

    expect($project->fresh()->stage)->toBe(ProjectStage::REVIEW);
});

test('it respects dry-run mode and does not alter the database', function () {
    $project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'stage' => ProjectStage::IMPORTED,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
    ]);

    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $assignment = ReviewAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $reviewer->id,
        ]);
        Review::factory()->create([
            'review_assignment_id' => $assignment->id,
            'status' => ReviewStatus::PENDENT,
        ]);
    }

    $this->artisan('projects:sync-stages', [
        '--selection' => $this->selection->id,
        '--dry-run' => true,
    ])
        ->expectsOutput('Dry-run concluído: 1 projeto(s) seriam atualizados.')
        ->assertSuccessful();

    expect($project->fresh()->stage)->toBe(ProjectStage::IMPORTED);
});

test('it advances project stage when all 3 evaluations are submitted', function () {
    $project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'stage' => ProjectStage::IMPORTED,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'modality' => ProjectModality::MASTER,
    ]);

    $reviewers = User::factory(3)->create();
    foreach ($reviewers as $reviewer) {
        $reviewer->assignRole($this->reviewerRole);
        $assignment = ReviewAssignment::factory()->create([
            'project_id' => $project->id,
            'user_id' => $reviewer->id,
        ]);
        Review::factory()->create([
            'review_assignment_id' => $assignment->id,
            'status' => ReviewStatus::SUBMITTED,
            'score' => ReviewScore::APPROVED, // 10
        ]);
    }

    $this->artisan('projects:sync-stages', [
        '--selection' => $this->selection->id,
    ])
        ->assertSuccessful();

    expect($project->fresh()->stage)->toBe(ProjectStage::WRITTEN_EXAM);
});

test('it updates stage to homologated for approved projects in distribution phase', function () {
    $this->selection->update(['phase' => SelectionProcessPhases::DISTRIBUTION]);

    $project = Project::factory()->create([
        'selection_process_id' => $this->selection->id,
        'stage' => ProjectStage::IMPORTED,
        'homologation_status' => ProjectHomologationStatus::APPROVED,
    ]);

    $this->artisan('projects:sync-stages', [
        '--selection' => $this->selection->id,
    ])
        ->expectsOutput('Sucesso: 1 projeto(s) sincronizados com sucesso.')
        ->assertSuccessful();

    expect($project->fresh()->stage)->toBe(ProjectStage::HOMOLOGATED);
});

test('it outputs information message when no projects need synchronization', function () {
    $this->artisan('projects:sync-stages', [
        '--selection' => $this->selection->id,
    ])
        ->expectsOutput('Todos os projetos já estão com estágios coerentes.')
        ->assertSuccessful();
});
