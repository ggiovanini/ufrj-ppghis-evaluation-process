<?php

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectStage;
use App\Exports\AffirmativeActionReportExport;
use App\Exports\CommitteeReportExport;
use App\Exports\DistributionReportExport;
use App\Exports\FinalResultReportExport;
use App\Exports\HomologationReportExport;
use App\Exports\ReviewReportExport;
use App\Exports\WrittenExamReportExport;
use App\Models\Project;
use App\Models\ReviewAssignment;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
});

test('a project manager can download all 7 selection process reports with valid filenames', function (string $routeName, string $filenamePrefix) {
    $manager = User::factory()->create();
    $manager->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create();

    $this->actingAs($manager)
        ->get(route($routeName, $selection))
        ->assertOk()
        ->assertHeader('content-disposition', "attachment; filename={$filenamePrefix}-{$selection->id}.xlsx");
})->with([
    ['selection.projects.homologation.report', 'relatorio-homologacao'],
    ['selection.projects.distribution.report', 'relatorio-distribuicao'],
    ['selection.projects.review.report', 'relatorio-avaliacao'],
    ['selection.projects.written-exam.report', 'relatorio-prova-escrita'],
    ['selection.projects.committee.report', 'relatorio-comite'],
    ['selection.projects.final-result.report', 'relatorio-resultado-final'],
    ['selection.projects.affirmative-action.report', 'relatorio-acoes-afirmativas'],
]);

test('all report export classes execute safely when selection process has zero projects', function () {
    $selection = SelectionProcess::factory()->create();

    $exports = [
        new HomologationReportExport($selection),
        new DistributionReportExport($selection),
        new ReviewReportExport($selection),
        new WrittenExamReportExport($selection),
        new CommitteeReportExport($selection),
        new FinalResultReportExport($selection),
        new AffirmativeActionReportExport($selection),
    ];

    foreach ($exports as $export) {
        $rows = $export->collection();
        expect($rows)->toHaveCount(0);
        expect($export->headings())->toBeArray()->not->toBeEmpty();
        expect($export->title())->toBeString()->not->toBeEmpty();
    }
});

test('all report export classes handle partial data, null relations, and missing scores without throwing errors', function () {
    $selection = SelectionProcess::factory()->create();

    $reviewer = User::factory()->create(['name' => 'Avaliador Teste']);

    $project = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato Teste',
        'homologation_status' => ProjectHomologationStatus::APPROVED,
        'stage' => ProjectStage::REVIEW,
        'review_score' => null,
        'written_exam_score' => null,
        'committee_score' => null,
        'final_score' => null,
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Sim',
        ],
    ]);

    ReviewAssignment::create([
        'project_id' => $project->id,
        'user_id' => $reviewer->id,
    ]);

    $homologationRows = (new HomologationReportExport($selection))->collection();
    expect($homologationRows)->toHaveCount(1);

    $distributionRows = (new DistributionReportExport($selection))->collection();
    expect($distributionRows)->toHaveCount(1);
    expect($distributionRows->first()[4])->toBe('Avaliador Teste');

    $reviewRows = (new ReviewReportExport($selection))->collection();
    expect($reviewRows)->toHaveCount(1);
    expect($reviewRows->first()[4])->toBe('Avaliador Teste (Não avaliado)');

    $writtenExamRows = (new WrittenExamReportExport($selection))->collection();
    expect($writtenExamRows)->toHaveCount(1);

    $committeeRows = (new CommitteeReportExport($selection))->collection();
    expect($committeeRows)->toHaveCount(1);
    expect($committeeRows->first()[7])->toBe('-');

    $finalResultRows = (new FinalResultReportExport($selection))->collection();
    expect($finalResultRows)->toHaveCount(1);

    $affirmativeActionRows = (new AffirmativeActionReportExport($selection))->collection();
    expect($affirmativeActionRows)->toHaveCount(1);
});
