<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Exceptions\ValidateImportException;
use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportProjectsService
{
    protected Collection $data;

    protected int $count = 0;

    public function __construct(
        public SelectionProcess $selectionProcess,
        public Project $entity,
        public ?Collection $filesIndex = null,
        public ?ProjectModality $modality = null,
    ) {}

    public function import(Collection $data): ?int
    {
        $this->data = $data;
        $this->validate();
        $this->perform();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->data->isEmpty()) {
            throw new ValidateImportException(
                'Importação não contem planilha.'
            );
        }
        $this->data = collect($this->data->first());
        if ($this->data->isEmpty()) {
            throw new ValidateImportException(
                'Planilha não contém dados.'
            );
        }
        /** @var Collection $firstItem */
        $firstItem = $this->data->first();
        if (! $firstItem) {
            throw new ValidateImportException(
                'Planilha não contém dados.'
            );
        }
        $firstKeys = array_keys($firstItem->toArray());

        if (Str::snake($firstKeys[0]) !== 'id_da_resposta') {
            throw new ValidateImportException(
                'A planilha parece não conter os dados esperados.'
            );
        }

        if (! in_array('deseja_concorrer_sob_o_sistema_de_acoes_afirmativas', $firstKeys)) {
            throw new ValidateImportException(
                'A planilha parece não conter a coluna de deseja_concorrer_sob_o_sistema_de_acoes_afirmativas.'
            );
        }
    }

    private function perform(): void
    {
        $existingRegisterIds = $this->entity->newQuery()
            ->withTrashed()
            ->where('selection_process_id', $this->selectionProcess->id)
            ->pluck('register_id')
            ->map(fn (string $registerId): string => $this->normalizeIdentifier($registerId))
            ->flip();

        foreach ($this->data as $projectData) {
            $projectData = $projectData->toArray();
            $registerId = $this->normalizeIdentifier((string) $projectData['id_da_resposta']);

            if ($existingRegisterIds->has($registerId)) {
                continue;
            }

            $projectModality = $this->projectModality($projectData);

            if ($this->modality !== null && $projectModality !== $this->modality) {
                continue;
            }

            $this->create($projectData, $projectModality);
            $existingRegisterIds->put($registerId, true);
        }
    }

    private function create(array $projectData, ProjectModality $modality): void
    {
        $project = new $this->entity;
        $title = collect($projectData)->first(
            fn (mixed $value, int|string $key): bool => filled($value)
                && str_contains(Str::snake((string) $key), 'titulo_do_projeto')
        ) ?? collect($projectData)
            ->filter(fn (mixed $value, int|string $key): bool => str_contains(Str::snake((string) $key), 'upload_do_projeto'))
            ->map(fn (mixed $value): mixed => is_string($value) ? json_decode($value, true) : null)
            ->map(fn (mixed $value): mixed => data_get($value, '0.title'))
            ->filter()
            ->sortByDesc(fn (mixed $value): int => strlen((string) $value))
            ->first();

        $project->fill([
            'candidate_name' => Str::trim($projectData['nome_completo']),
            'register_id' => Str::trim($projectData['id_da_resposta']),
            'submitted_at' => $this->submittedAt($projectData['data_de_envio'] ?? null),
            'title' => Str::trim((string) ($title ?? '')),
            'description' => Str::trim($projectData['resumo'] ?? null),
            'indication' => Str::trim($projectData['indicacao_de_especialista_do_corpo_docente_do_ppghis_para_avaliacao_do_projeto_de_pesquisa'] ?? null),
            'modality' => $modality->value,
            'original_content' => $projectData,
            'content' => null,
            'homologation_status' => ProjectHomologationStatus::PENDING->value,
            'stage' => ProjectStage::IMPORTED->value,
        ]);

        $this->selectionProcess->projects()->save($project);
        $this->handleOriginalContent($project);

        $this->count++;
    }

    private function submittedAt(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse((string) $value);
    }

    private function projectModality(array $projectData): ProjectModality
    {
        return Str::trim(Str::lower($projectData['curso'])) === 'mestrado'
            ? ProjectModality::MASTER
            : ProjectModality::DOCTORATE;
    }

    private function handleOriginalContent(Project $project): void
    {
        $project->refresh();
        $originalContent = $project->original_content;

        if (empty($originalContent)) {
            return;
        }

        $content = [];
        $documents = [];

        $ignoreKeys = [
            'id_da_resposta',
            'ultima_pagina',
            'idioma_inicial',
            'semente',
            'data_de_inicio',
            'data_da_ultima_acao',
            'dados_pessoais',
            'formacao',
            'projeto',
            'lingua_estrangeira',
            'nome_completo',
            'titulo_do_projeto',
            'resumo',
            'indicacao_de_especialista_do_corpo_docente_do_ppghis_para_avaliacao_do_projeto_de_pesquisa',
            'curso',
        ];

        foreach ($originalContent as $key => $value) {
            if (in_array($key, $ignoreKeys) || str_starts_with((string) $key, 'filecount_')) {
                continue;
            }

            if (empty($value)) {
                continue;
            }

            $label = Str::headline((string) $key);
            $label = str_replace('Faca O Upload Da', '', $label);
            $label = str_replace('Faca O Upload De', '', $label);
            $label = str_replace('Faca O Upload Do', '', $label);
            $label = str_replace('Faca Upload Do', '', $label);
            $label = Str::trim($label);

            $decoded = null;
            if (is_string($value) && str_starts_with($value, '[{')) {
                $decoded = json_decode($value, true);
            }

            if ($label === '71') {
                $label = $value;
                $value = 'Sim';
            }

            if (is_array($decoded) && isset($decoded[0]['filename'])) {
                foreach ($decoded as $file) {
                    $document = [
                        'label' => $label,
                        'name' => $file['name'] ?? null,
                        'filename' => $file['filename'],
                        'ext' => $file['ext'] ?? null,
                        'size' => $file['size'] ?? null,
                    ];

                    $matchedFile = $this->findExtractedFile($project, $document['name']);
                    if ($matchedFile !== null) {
                        $document['path'] = $matchedFile['path'];
                        $document['url'] = $matchedFile['url'];
                    }

                    $documents[] = $document;
                }
            } else {
                $content[] = [
                    'label' => $label,
                    'value' => $value,
                ];
            }
        }

        $project->update([
            'content' => [
                'content' => $content,
                'documents' => $documents,
            ],
        ]);
    }

    /**
     * @return array{project_id: string, document_name: string, path: string, url: string}|null
     */
    private function findExtractedFile(Project $project, ?string $documentName): ?array
    {
        if ($this->filesIndex === null || blank($documentName)) {
            return null;
        }

        $normalizedProjectId = $this->normalizeIdentifier($project->register_id);
        $normalizedDocumentName = $this->normalizeFilename($documentName);

        $result = $this->filesIndex->first(function (array $file) use ($normalizedProjectId, $normalizedDocumentName): bool {
            return $this->normalizeIdentifier($file['project_id']) === $normalizedProjectId
                && $this->normalizeFilename($file['document_name']) === $normalizedDocumentName;
        });

        return is_array($result) ? $result : null;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $normalized = ltrim(Str::trim($identifier), '0');

        return $normalized === '' ? '0' : $normalized;
    }

    private function normalizeFilename(string $filename): string
    {
        return Str::lower(Str::ascii(basename(Str::trim($filename))));
    }
}
