<?php

namespace Database\Seeders;

use App\Models\ReviewForm;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        ReviewForm::factory()->create([
            'name' => 'Formulário de Avaliação',
            'version' => '1.0',
            'schema' => [
                'fields' => [
                    [
                        'id' => 1,
                        'label' => 'A questão central é apresentada de forma clara e está bem desenvolvida?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 2,
                        'label' => 'O tema proposto é relevante para o campo de conhecimento em que se insere?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 3,
                        'label' => 'O autor discutiu a bibliografia pertinente ao tema?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 4,
                        'label' => 'O autor apresenta de forma clara suas perspectivas teóricas?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 5,
                        'label' => 'O texto é claro e bem escrito?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 6,
                        'label' => 'O corpus documental proposto condiz com o desenvolvimento da pesquisa?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 7,
                        'label' => 'A metodologia adequa-se às perspectivas teóricas do autor e ao corpus documental selecionado?',
                        'type' => 'select',
                        'required' => true,
                        'options' => [
                            'Sim',
                            'Não',
                            'Em parte',
                        ],
                    ],
                    [
                        'id' => 8,
                        'label' => 'Se você respondeu "em parte" para algum dos itens acima, desenvolva abaixo sua resposta.',
                        'type' => 'text',
                        'required' => false,
                        'options' => [],
                    ],
                ],
            ],
            'active' => true,
        ]);
    }
}
