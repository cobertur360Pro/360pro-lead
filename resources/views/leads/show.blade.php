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

    <hr>

    <h2>Qualificação comercial</h2>
    
    <form method="POST" action="{{ route('leads.qualificacao', $lead->id) }}">
        @csrf
    
        <label>Origem:</label>
        <select name="origem">
            <option value="">Selecione</option>
            <option value="site">Site</option>
            <option value="whatsapp">WhatsApp</option>
            <option value="indicacao">Indicação</option>
            <option value="instagram">Instagram</option>
        </select>
    
        <br><br>
    
        <label>Interesse:</label>
        <input type="text" name="interesse" placeholder="Ex: cobertura, sacada...">
    
        <br><br>
    
        <label>Urgência:</label>
        <select name="urgencia">
            <option value="">Selecione</option>
            <option value="baixa">Baixa</option>
            <option value="media">Média</option>
            <option value="alta">Alta</option>
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

    <h2>Observações</h2>
    <form method="POST" action="{{ route('leads.observacoes', $lead->id) }}">
        @csrf
        <textarea name="observacoes" rows="6" cols="80" placeholder="Digite observações sobre este lead...">{{ $lead->observacoes }}</textarea>
        <br><br>
        <button type="submit">Salvar observações</button>
    </form>

    <hr>

    <h2>Nova interação</h2>
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

    <h2>Histórico de interações</h2>

    @forelse($lead->interactions as $interaction)
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p><strong>Tipo:</strong> {{ $interaction->tipo }}</p>
            <p><strong>Data:</strong> {{ $interaction->created_at }}</p>
            <p><strong>Conteúdo:</strong><br>{{ $interaction->conteudo }}</p>
        </div>
    @empty
        <p>Nenhuma interação registrada ainda.</p>
    @endforelse

    <br>
    <a href="{{ route('leads.index') }}">Voltar para leads</a>
</body>
</html>
