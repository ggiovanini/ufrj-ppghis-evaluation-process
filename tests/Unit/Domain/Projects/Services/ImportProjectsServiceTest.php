<?php

namespace Tests\Unit\Domain\Projects\Services;

use App\Domain\Projects\Services\ImportProjectsService;
use App\Domain\Projects\Services\PotentialDuplicateProjectService;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ImportProjectsServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_groups_potential_duplicate_projects_by_name_or_normalized_title(): void
    {
        $selectionProcess = SelectionProcess::factory()->create();
        $firstProject = Project::factory()->create([
            'selection_process_id' => $selectionProcess->id,
            'candidate_name' => 'Ana Maria da Silva',
            'title' => 'História do Brasil: 1900',
            'content' => ['documents' => [['name' => 'a.pdf']]],
        ]);
        $secondProject = Project::factory()->create([
            'selection_process_id' => $selectionProcess->id,
            'candidate_name' => 'ANA MARIA DA SILVA',
            'title' => 'Outro título',
            'content' => ['documents' => [['name' => 'a.pdf'], ['name' => 'b.pdf']]],
        ]);
        $thirdProject = Project::factory()->create([
            'selection_process_id' => $selectionProcess->id,
            'candidate_name' => 'Outro candidato',
            'title' => 'Historia do Brasil 1900',
            'content' => ['documents' => []],
        ]);

        $duplicates = (new PotentialDuplicateProjectService)->analyze(
            $selectionProcess->projects()->get(),
        );

        expect($duplicates[$firstProject->id]['potential_duplicate'])->toBeTrue()
            ->and($duplicates[$firstProject->id]['duplicate_group'])
            ->toBe($duplicates[$secondProject->id]['duplicate_group'])
            ->and($duplicates[$firstProject->id]['duplicate_group'])
            ->toBe($duplicates[$thirdProject->id]['duplicate_group'])
            ->and($duplicates[$thirdProject->id]['duplicate_match_reasons'])
            ->toContain('título do projeto');
    }

    public function test_it_imports_the_project_submission_date(): void
    {
        $selectionProcess = SelectionProcess::factory()->create();
        $service = new ImportProjectsService($selectionProcess, new Project);

        $service->import(new Collection([
            new Collection([
                new Collection([
                    'id_da_resposta' => '123',
                    'data_de_envio' => '2025-06-17 13:22:37',
                    'nome_completo' => 'João Silva',
                    'titulo_do_projeto' => 'Meu Projeto',
                    'resumo' => 'Um resumo aqui',
                    'curso' => 'Mestrado',
                    'deseja_concorrer_sob_o_sistema_de_acoes_afirmativas' => 'Não',
                ]),
            ]),
        ]));

        expect($selectionProcess->projects()->firstOrFail()->submitted_at->toDateTimeString())
            ->toBe('2025-06-17 13:22:37');
    }

    public function test_handle_original_content_structures_content_and_documents()
    {
        $selectionProcess = SelectionProcess::factory()->create();
        $project = Project::factory()->create([
            'selection_process_id' => $selectionProcess->id,
            'original_content' => [
                'nome_completo' => 'João Silva',
                'id_da_resposta' => '123',
                'titulo_do_projeto' => 'Meu Projeto',
                'resumo' => 'Um resumo aqui',
                'curso' => 'Mestrado',
                'outra_informacao' => 'Valor importante',
                'faca_o_upload_do_projeto' => '[{ "title":"Projeto","comment":"","size":100,"name":"projeto.pdf","filename":"fu_123","ext":"pdf" }]',
                'filecount_faca_o_upload_do_projeto' => 1,
                'semente' => 'abc',
            ],
        ]);

        $service = new ImportProjectsService($selectionProcess, new Project);

        // We need to call handleOriginalContent. Since it's private, we can use reflection or
        // just call a public method that triggers it.
        // Actually, in the original code, it's called inside create().
        // But here we want to test it specifically.

        $reflection = new \ReflectionClass(ImportProjectsService::class);
        $method = $reflection->getMethod('handleOriginalContent');
        $method->setAccessible(true);
        $method->invokeArgs($service, [$project]);

        $project->refresh();

        $this->assertIsArray($project->content);
        $this->assertArrayHasKey('content', $project->content);
        $this->assertArrayHasKey('documents', $project->content);

        // Check content
        $content = collect($project->content['content']);
        $this->assertTrue($content->contains('label', 'Outra Informacao'));
        $this->assertTrue($content->contains('value', 'Valor importante'));

        // Should NOT contain technical fields or already mapped fields (if we choose to exclude them)
        // For now let's see what we decided.

        // Check documents
        $documents = collect($project->content['documents']);
        $this->assertCount(1, $documents);
        $this->assertEquals('Projeto', $documents->first()['label']);
        $this->assertEquals('projeto.pdf', $documents->first()['name']);
        $this->assertEquals('fu_123', $documents->first()['filename']);
    }

    public function test_it_links_a_document_to_an_extracted_archive_file(): void
    {
        $selectionProcess = SelectionProcess::factory()->create();
        $project = Project::factory()->create([
            'selection_process_id' => $selectionProcess->id,
            'register_id' => '37',
            'original_content' => [
                'id_da_resposta' => '37',
                'faca_o_upload_do_historico' => '[{"name":"danieldeassis-historico.pdf","filename":"fu_history","ext":"pdf"}]',
            ],
        ]);

        $service = new ImportProjectsService(
            $selectionProcess,
            new Project,
            collect([
                [
                    'project_id' => '00037',
                    'document_name' => 'danieldeassis-historico.pdf',
                    'path' => 'selecao/documentos/00037_06-Q128_00-danieldeassis-historico.pdf',
                    'url' => 'http://localhost/storage/selecao/documentos/00037_06-Q128_00-danieldeassis-historico.pdf',
                ],
            ]),
        );

        $reflection = new \ReflectionClass(ImportProjectsService::class);
        $method = $reflection->getMethod('handleOriginalContent');
        $method->setAccessible(true);
        $method->invokeArgs($service, [$project]);

        $document = collect($project->fresh()->content['documents'])->first();

        expect($document['path'])->toBe('selecao/documentos/00037_06-Q128_00-danieldeassis-historico.pdf')
            ->and($document['url'])->toBe('http://localhost/storage/selecao/documentos/00037_06-Q128_00-danieldeassis-historico.pdf');
    }
}
