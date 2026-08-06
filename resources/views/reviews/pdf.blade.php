<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Parecer - {{ $project->candidate_name }}</title>
    <style>
        @page { margin: 24px 30px; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        h2 { border-bottom: 1px solid #cbd5e1; font-size: 14px; margin: 22px 0 8px; padding-bottom: 4px; }
        h3 { font-size: 11px; margin: 0 0 4px; }
        p { margin: 0; white-space: pre-wrap; }
        .header { border-bottom: 2px solid #172033; padding-bottom: 12px; }
        .meta { color: #475569; margin-top: 6px; }
        .field { border-bottom: 1px solid #e2e8f0; padding: 8px 0; page-break-inside: avoid; }
        .score { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 10px; }
        .score strong { font-size: 13px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $project->candidate_name }}</h1>
        <div class="meta">
            <strong>{{ $project->title }}</strong><br>
            ID: {{ $project->register_id }}<br>
            Modalidade: {{ $project->modality->label() }}<br>
            Avaliador: {{ $reviewer->name }}
        </div>
    </div>

    <h2>Formulário de avaliação</h2>
    @forelse ($fields as $field)
        <div class="field">
            <h3>{{ $field['label'] ?? 'Pergunta' }}</h3>
            <p>{{ data_get($review->answers, $field['id']) ?: 'Não respondido' }}</p>
        </div>
    @empty
        <p>Nenhuma pergunta registrada.</p>
    @endforelse

    <h2>Resultado</h2>
    <div class="score">
        <strong>{{ $review->score->label() }}</strong><br>
        {{ $review->score->description() }}
    </div>

    <div class="field">
        <h3>Justificativa do parecer</h3>
        <p>{{ $review->comments ?: 'Nenhuma justificativa fornecida.' }}</p>
    </div>
    <div class="field">
        <h3>Pergunta sugerida para a prova oral</h3>
        <p>{{ $review->questions ?: 'Nenhuma pergunta sugerida.' }}</p>
    </div>
</body>
</html>
