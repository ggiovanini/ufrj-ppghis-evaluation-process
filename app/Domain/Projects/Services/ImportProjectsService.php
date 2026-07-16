<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Exceptions\ValidateImportException;
use App\Domain\Projects\Types\ProjectModality;
use App\Domain\Projects\Types\ProjectStage;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ImportProjectsService
{
    protected Collection $data;

    protected int $count = 0;

    public function __construct(
        public SelectionProcess $selectionProcess,
        public Project $entity,
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
        if (!$firstItem) {
            throw new ValidateImportException(
                'Planilha não contém dados.'
            );
        }
        $firstKeys = array_keys($firstItem->toArray());
        if (
            $firstKeys[0] !== 'id_da_resposta' ||
            $firstKeys[68] !== 71
        ) {
            throw new ValidateImportException(
                'A planilha parece não conter os dados esperados.'
            );
        }
    }

    private function perform(): void
    {
        foreach ($this->data as $projectData) {
            $this->create($projectData->toArray());
        }
    }

    private function create(array $projectData): void
    {
        $project = new $this->entity();
        $project->fill([
            'candidate_name' => Str::trim($projectData['nome_completo']),
            'title' => Str::trim($projectData['titulo_do_projeto']),
            'modality' => Str::trim(Str::lower($projectData['curso'])) === 'mestrado'
                ? ProjectModality::MASTER->value
                : ProjectModality::DOCTORATE->value,
            'original_content' => $projectData,
            'content' => null,
            'stage' => ProjectStage::IMPORTED->value,
        ]);

        $this->selectionProcess->projects()->save($project);
        $this->count++;
    }
}
