<?php

namespace App\Exports;

use App\Domain\Projects\Types\ProjectHomologationStatus;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class DistributionReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly SelectionProcess $selection) {}

    public function collection(): Collection
    {
        return $this->selection->projects()
            ->where('homologation_status', ProjectHomologationStatus::APPROVED)
            ->orderBy('candidate_name')
            ->get()
            ->map(function ($project): array {
                $reviewers = $project->reviewAssignments->map(fn ($reviewAssignment) => $reviewAssignment->user->name);

                return [
                    $project->register_id,
                    $project->candidate_name,
                    $project->modality->label(),
                    $project->title,
                    $reviewers->implode(', '),
                    $project->stage->label(),
                    $project->rejected_on_stage?->label() ?? '',
                    $project->updated_at->format('d/m/Y H:i'),
                ];
            });
    }

    public function headings(): array
    {
        return ['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'Avaliadores', 'Status', 'Reprovado', 'Atualização'];
    }

    public function title(): string
    {
        return 'Distribuição';
    }
}
