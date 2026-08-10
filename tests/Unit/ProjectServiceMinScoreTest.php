<?php

use App\Domain\Projects\Services\ProjectService;
use App\Models\Project;

test('it returns the affirmative action minimum score', function () {
    $project = new Project([
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Sim',
        ],
    ]);

    expect((new ProjectService($project))->minScoreRule())->toBe(600);
});

test('it returns the standard minimum score', function () {
    $project = new Project([
        'original_content' => [
            'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Não',
        ],
    ]);

    expect((new ProjectService($project))->minScoreRule())->toBe(700);
});
