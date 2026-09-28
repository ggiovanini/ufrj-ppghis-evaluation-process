<?php

use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Exports\AffirmativeActionReportExport;
use App\Models\Project;
use App\Models\SelectionProcess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

beforeEach(function () {
    Permission::create(['name' => 'projects.manage', 'guard_name' => 'web']);
});

test('a project manager can download affirmative action report', function () {
    $manager = User::factory()->create();
    $manager->givePermissionTo('projects.manage');

    $selection = SelectionProcess::factory()->create();

    $affirmativeProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato Afirmativo',
        'register_id' => '101',
        'modality' => ProjectModality::MASTER,
        'title' => 'Projeto Ação Afirmativa',
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Sim',
        ],
        'stage' => ProjectStage::HOMOLOGATED,
    ]);

    $nonAffirmativeProject = Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato Ampla Concorrência',
        'register_id' => '102',
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Não',
        ],
    ]);

    $this->actingAs($manager)
        ->get(route('selection.projects.affirmative-action.report', $selection))
        ->assertOk()
        ->assertHeader('content-disposition', 'attachment; filename=relatorio-acoes-afirmativas-'.$selection->id.'.xlsx');

    $export = new AffirmativeActionReportExport($selection);
    $rows = $export->collection();

    expect($rows)->toHaveCount(1);
    expect($rows->first()[0])->toBe('101');
    expect($rows->first()[1])->toBe('Candidato Afirmativo');
    expect($rows->first()[4])->toBe('Sim');
    expect($export->headings())->toBe(['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'Ações Afirmativas', 'Status', 'Reprovado', 'Atualização']);
    expect($export->title())->toBe('Ações Afirmativas');
});

test('affirmative action report correctly filters candidates case-insensitively and handles variations', function () {
    $selection = SelectionProcess::factory()->create();

    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato 1',
        'original_content' => ['deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'sim'],
    ]);

    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato 2',
        'original_content' => ['deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'SIM - Cotas'],
    ]);

    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato 3',
        'original_content' => ['deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Não'],
    ]);

    Project::factory()->create([
        'selection_process_id' => $selection->id,
        'candidate_name' => 'Candidato 4',
        'original_content' => [],
    ]);

    $export = new AffirmativeActionReportExport($selection);
    $rows = $export->collection();

    expect($rows)->toHaveCount(2);
    expect($rows->pluck(1)->all())->toBe(['Candidato 1', 'Candidato 2']);
});

test('unauthenticated users cannot download affirmative action report', function () {
    $selection = SelectionProcess::factory()->create();

    $this->get(route('selection.projects.affirmative-action.report', $selection))
        ->assertRedirect('/login');
});

test('users without projects.manage permission are forbidden from downloading affirmative action report', function () {
    $user = User::factory()->create();
    $selection = SelectionProcess::factory()->create();

    $this->actingAs($user)
        ->get(route('selection.projects.affirmative-action.report', $selection))
        ->assertForbidden();
});
