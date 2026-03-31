<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversas - Lead360 AI</title>
</head>
<body>
    <h1>Histórico geral de interações</h1>

    @forelse($interactions as $interaction)
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p><strong>Lead:</strong> {{ $interaction->lead?->nome }}</p>
            <p><strong>Tipo:</strong> {{ $interaction->tipo }}</p>
            <p><strong>Data:</strong> {{ $interaction->created_at }}</p>
            <p><strong>Conteúdo:</strong><br>{{ $interaction->conteudo }}</p>
        </div>
    @empty
        <p>Nenhuma interação registrada ainda.</p>
    @endforelse

    <br>
    <a href="{{ route('home') }}">Voltar</a>
</body>
</html>
