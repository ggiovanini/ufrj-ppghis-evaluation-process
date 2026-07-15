<?php

namespace App\Domain\Projects\Services;

use App\Domain\Projects\Exceptions\ValidateImportException;
use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;

class ImportProjectsService
{
    protected Collection $data;
    protected ?int $count = null;

    public function __construct(
        public SelectionProcess $selectionProcess,
        public Project $entity,
    ){}

    public function import(Collection $data): ?int
    {
        $this->data = $data;
        $this->validate();
        $this->perform();

        return $this->count;
    }

    private function validate(): void
    {
        if ($this->data->isEmpty())
            throw new ValidateImportException(
                'Não foi possível validar os dados para importação.'
            );
    }

    private function perform(): void
    {
        foreach ($this->data as $projectData) {
            $this->create(collect($projectData));
        }
    }

    private function create(Collection $projectData): void
    {
        $preparatedData = $projectData->only(['id']);
        $this->entity->create($preparatedData->toArray());
        $this->count++;
    }
}
