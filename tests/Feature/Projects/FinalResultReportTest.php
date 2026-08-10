<?php

use App\Models\FinalResult;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

test('a project manager can download the final result report', function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->givePermissionTo('projects.manage');
    $selection = SelectionProcess::factory()->create();
    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'review_score' => 80,
        'written_exam_score' => 70,
        'committee_score' => 90,
        'final_score' => 82,
    ]);
    FinalResult::create([
        'project_id' => $project->id,
        'review_average' => 0.8,
        'written_exam_score' => 70,
        'committee_score' => 90,
        'final_score' => 82,
        'passed' => true,
    ]);

    $this->actingAs($user)
        ->get(route('selection.projects.final-result.report', $selection))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=relatorio-resultado-final-'.$selection->id.'.xlsx');
});
