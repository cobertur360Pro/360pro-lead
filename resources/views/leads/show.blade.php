<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lead #{{ $lead->id }} - Lead360 AI</title>
</head>
<body>
    <h1>Lead #{{ $lead->id }}</h1>

    <p><strong>Nome:</strong> {{ $lead->nome }}</p>
    <p><strong>Telefone:</strong> {{ $lead->telefone }}</p>
    <p><strong>Cidade:</strong> {{ $lead->cidade }}</p>
    <p><strong>Status:</strong> {{ $lead->status }}</p>

    <hr>

    <h2>Qualificação comercial</h2>

    <form method="POST" action="{{ route('leads.qualificacao', $lead->id) }}">
        @csrf

        <label>Origem:</label>
        <select name="origem">
            <option value="">Selecione</option>
            <option value="site" {{ $lead->origem === 'site' ? 'selected' : '' }}>Site</option>
            <option value="whatsapp" {{ $lead->origem === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            <option value="indicacao" {{ $lead->origem === 'indicacao' ? 'selected' : '' }}>Indicação</option>
            <option value="instagram" {{ $lead->origem === 'instagram' ? 'selected' : '' }}>Instagram</option>
        </select>

        <br><br>

        <label>Interesse:</label>
        <input type="text" name="interesse" placeholder="Ex: cobertura, sacada..." value="{{ $lead->interesse }}">

        <br><br>

        <label>Urgência:</label>
        <select name="urgencia">
            <option value="">Selecione</option>
            <option value="baixa" {{ $lead->urgencia === 'baixa' ? 'selected' : '' }}>Baixa</option>
            <option value="media" {{ $lead->urgencia === 'media' ? 'selected' : '' }}>Média</option>
            <option value="alta" {{ $lead->urgencia === 'alta' ? 'selected' : '' }}>Alta</option>
        </select>

        <br><br>

        <button type="submit">Salvar qualificação</button>
    </form>

    <hr>

    <h3>Resumo comercial</h3>

    <p><strong>Origem:</strong> {{ $lead->origem }}</p>
    <p><strong>Interesse:</strong> {{ $lead->interesse }}</p>
    <p><strong>Urgência:</strong> {{ $lead->urgencia }}</p>
    <p><strong>Temperatura:</strong> {{ $lead->temperatura }}</p>
    <p><strong>Score:</strong> {{ $lead->score }}</p>

    <hr>

    <h2>Observações</h2>
    <form method="POST" action="{{ route('leads.observacoes', $lead->id) }}">
        @csrf
        <textarea name="observacoes" rows="6" cols="80" placeholder="Digite observações sobre este lead...">{{ $lead->observacoes }}</textarea>
        <br><br>
        <button type="submit">Salvar observações</button>
    </form>

    <hr>

    <h2>Nova interação manual</h2>
    <form method="POST" action="{{ route('leads.interacoes.store', $lead->id) }}">
        @csrf
        <label>Tipo:</label>
        <select name="tipo">
            <option value="mensagem">Mensagem</option>
            <option value="ligacao">Ligação</option>
            <option value="nota">Nota</option>
            <option value="followup">Follow-up</option>
        </select>
        <br><br>

        <label>Conteúdo:</label><br>
        <textarea name="conteudo" rows="5" cols="80" placeholder="Digite o conteúdo da interação..." required></textarea>
        <br><br>

        <button type="submit">Registrar interação</button>
    </form>

    <hr>

    <h2>Teste da IA</h2>
    <form method="POST" action="{{ route('leads.ia', $lead->id) }}">
        @csrf
        <textarea name="mensagem_ia" rows="5" cols="80" placeholder="Digite aqui uma mensagem como se fosse o cliente..." required></textarea>
        <br><br>
        <button type="submit">Perguntar para a IA</button>
    </form>

    <hr>

    <h2>Histórico de interações</h2>

    @forelse($lead->interactions as $interaction)
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p><strong>Tipo:</strong> {{ $interaction->tipo }}</p>
            <p><strong>Data:</strong> {{ $interaction->created_at }}</p>
            <p><strong>Conteúdo:</strong><br>{{ $interaction->conteudo }}</p>

            @if($interaction->resposta_ia)
                <hr>
                <p><strong>Resposta da IA:</strong><br>{{ $interaction->resposta_ia }}</p>
            @endif
        </div>
    @empty
        <p>Nenhuma interação registrada ainda.</p>
    @endforelse

    <br>
    <a href="{{ route('leads.index') }}">Voltar para leads</a>
</body>
</html>
