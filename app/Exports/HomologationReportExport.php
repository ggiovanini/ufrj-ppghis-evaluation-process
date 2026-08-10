<?php

namespace App\Exports;

use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class HomologationReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly SelectionProcess $selection) {}

    public function collection(): Collection
    {
        return $this->selection->projects()
            ->orderBy('candidate_name')
            ->get()
            ->map(fn ($project): array => [
                $project->register_id,
                $project->candidate_name,
                $project->modality->label(),
                $project->title,
                $project->homologation_status->label(),
                $project->homologation_reason,
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'Resultado', 'Motivo'];
    }

    public function title(): string
    {
        return 'Homologação';
    }
}
