<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\SelectionProcess\Services\WrittenExamService;
use App\Models\ReviewForm;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('it stores the written exam and keeps the project score synchronized', function () {
    $reviewForm = ReviewForm::create([
        'name' => 'Formulário',
        'version' => '1',
        'schema' => [],
    ]);
    $selection = SelectionProcess::create([
        'name' => 'Seleção',
        'year' => 2026,
        'review_form_id' => $reviewForm->id,
    ]);
    $project = $selection->projects()->create([
        'register_id' => '1',
        'candidate_name' => 'Candidato',
        'title' => 'Projeto',
        'modality' => ProjectModality::MASTER,
        'original_content' => [],
    ]);
    $user = User::create([
        'name' => 'Avaliador',
        'email' => 'avaliador@example.com',
        'password' => 'password',
    ]);

    app(WrittenExamService::class)->update($project, 875, $user);

    expect($project->fresh()->written_exam_score)->toBe(875)
        ->and($project->writtenExam()->first()->score)->toBe(875)
        ->and($project->writtenExam()->first()->passed)->toBeTrue()
        ->and($project->writtenExam()->first()->user_id)->toBe($user->id);
});
