<?php

namespace App\Exports;

use App\Models\Project;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class AffirmativeActionReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly SelectionProcess $selection) {}

    public function collection(): Collection
    {
        return $this->selection->projects()
            ->orderBy('candidate_name')
            ->get()
            ->filter(fn (Project $project): bool => $project->isAffirmativeAction())
            ->values()
            ->map(fn (Project $project): array => [
                $project->register_id,
                $project->candidate_name,
                $project->modality?->label() ?? '',
                $project->title,
                $project->original_content['deseja_concorrer_sob_o_sistema_de_acoes_afirmativas'] ?? 'Sim',
                $project->stage?->label() ?? '',
                $project->rejected_on_stage?->label() ?? '',
                $project->updated_at?->format('d/m/Y H:i') ?? '',
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'Ações Afirmativas', 'Status', 'Reprovado', 'Atualização'];
    }

    public function title(): string
    {
        return 'Ações Afirmativas';
    }
}
