<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Avaliação de projeto - {{ $project->candidate_name }}</title>
    <style>
        @page { margin: 36px 44px; }
        * { box-sizing: border-box; }
        body { color: #172033; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.5; margin: 0; padding: 0; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        h2 { background: #f8fafc; border-bottom: 1px solid #cbd5e1; font-size: 14px; margin: 0; padding: 9px 14px; }
        h3 { font-size: 11px; margin: 0 0 4px; }
        p { margin: 0; white-space: pre-wrap; }
        .header { border: 1px solid #94a3b8; margin-bottom: 14px; padding: 18px 20px; }
        .subtitle { color: #475569; font-size: 10px; letter-spacing: 1px; margin-bottom: 4px; text-transform: uppercase; }
        .project-title { font-size: 13px; font-weight: bold; }
        .meta { color: #475569; margin-top: 9px; }
        .section { border: 1px solid #cbd5e1; margin-bottom: 14px; }
        .field { border-bottom: 1px solid #e2e8f0; padding: 10px 14px; page-break-inside: avoid; }
        .field:last-child { border-bottom: 0; }
        .score { background: #f1f5f9; margin: 12px 14px; padding: 12px 14px; }
        .score strong { font-size: 13px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="subtitle">Documento de avaliação</div>
        <h1>Avaliação de projeto</h1>
        <div class="project-title">{{ $project->candidate_name }}</div>
        <div class="meta">
            Projeto: {{ $project->title }}<br>
            ID: {{ $project->register_id }}<br>
            Modalidade: {{ $project->modality->label() }}<br>
            Avaliador: {{ $reviewer->name }}
        </div>
    </div>

    <div class="section">
        <h2>Formulário de avaliação</h2>
        @forelse ($fields as $field)
            <div class="field">
                <h3>{{ $field['label'] ?? 'Pergunta' }}</h3>
                <p>{{ data_get($review->answers, $field['id']) ?: 'Não respondido' }}</p>
            </div>
        @empty
            <div class="field">
                <p>Nenhuma pergunta registrada.</p>
            </div>
        @endforelse
    </div>

    <div class="section">
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
    </div>
</body>
</html>
