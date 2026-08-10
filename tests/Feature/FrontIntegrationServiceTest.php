<?php

use App\Domain\Front\Services\FrontIntegrationService;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\SelectionProcess\Types\SelectionProcessPhases;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it calculates preliminary written exam results using each project rule', function () {
    $selection = SelectionProcess::factory()->create([
        'phase' => SelectionProcessPhases::WRITTEN_EXAM,
    ]);
    $user = User::create([
        'name' => 'Avaliador',
        'email' => 'avaliador@example.com',
        'password' => 'password',
    ]);

    $passedProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
        'written_exam_score' => 600,
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Sim',
        ],
    ]);
    $failedProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
        'written_exam_score' => 699,
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Não',
        ],
    ]);
    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'modality' => ProjectModality::MASTER,
        'review_score' => 800,
        'written_exam_score' => null,
    ]);

    $passedProject->writtenExam()->create(['score' => 600, 'passed' => true, 'user_id' => $user->id]);
    $failedProject->writtenExam()->create(['score' => 699, 'passed' => false, 'user_id' => $user->id]);

    $stats = FrontIntegrationService::selectionProcessStats($selection);

    expect($stats['written_exams'])->toBe(3)
        ->and($stats['written_examined'])->toBe(2)
        ->and($stats['written_exam_preliminarily_passed'])->toBe(1)
        ->and($stats['written_exam_preliminarily_failed'])->toBe(1);
});
