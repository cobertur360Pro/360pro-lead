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
