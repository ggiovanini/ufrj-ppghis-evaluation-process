<?php

namespace App\Exports;

use App\Domain\Projects\Types\ProjectScore;
use App\Models\SelectionProcess;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class WrittenExamReportExport implements FromCollection, WithHeadings, WithTitle
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
                ProjectScore::make($project->review_score)->format(),
                ProjectScore::make($project->written_exam_score)->format(),
                $project->stage->label(),
                $project->rejected_on_stage?->label() ?? '',
                $project->updated_at->format('d/m/Y H:i'),
            ]);
    }

    public function headings(): array
    {
        return ['ID', 'Candidato', 'Modalidade', 'Título do projeto', 'NMA', 'NP', 'Status', 'Reprovado', 'Atualização'];
    }

    public function title(): string
    {
        return 'Prova escrita';
    }
}
