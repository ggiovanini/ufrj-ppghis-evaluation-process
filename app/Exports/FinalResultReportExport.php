<?php

namespace App\Exports;

use App\Domain\Projects\Types\ProjectScore;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class FinalResultReportExport implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private readonly SelectionProcess $selection) {}

    public function collection(): Collection
    {
        return $this->selection->projects()
            ->with('finalResults')
            ->orderBy('candidate_name')
            ->get()
            ->map(fn ($project): array => [
                $project->register_id,
                $project->candidate_name,
                $project->modality->label(),
                $project->title,
                ProjectScore::make($project->review_score)->format(),
                ProjectScore::make($project->written_exam_score)->format(),
                ProjectScore::make($project->committee_score)->format(),
                ProjectScore::make($project->final_score)->format(),
                $project->finalResults?->passed === null
                    ? ''
                    : ($project->finalResults->passed ? 'Aprovado' : 'Reprovado'),
                $project->stage->label(),
                $project->rejected_on_stage?->label() ?? '',
                $project->updated_at->format('d/m/Y H:i'),
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'NMA', 'NP', 'NC', 'Nota final', 'Resultado final', 'Status', 'Reprovado', 'Atualização'];
    }

    public function title(): string
    {
        return 'Resultado final';
    }
}
