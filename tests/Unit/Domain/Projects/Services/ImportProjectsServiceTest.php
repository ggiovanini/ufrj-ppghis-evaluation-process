<?php

namespace Tests\Unit\Domain\Projects\Services;

use App\Domain\Projects\Services\ImportProjectsService;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportProjectsServiceTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertEquals('Faca O Upload Do Projeto', $documents->first()['label']);
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
