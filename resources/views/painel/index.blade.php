<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Lead360 AI</title>
</head>
<body>
    <h1>Painel Lead360 AI</h1>

    <ul>
        <li><strong>Total de leads:</strong> {{ $totalLeads }}</li>
        <li><strong>Novo:</strong> {{ $totalNovo }}</li>
        <li><strong>Contato:</strong> {{ $totalContato }}</li>
        <li><strong>Orçamento:</strong> {{ $totalOrcamento }}</li>
        <li><strong>Fechado:</strong> {{ $totalFechado }}</li>
        <li><strong>Perdido:</strong> {{ $totalPerdido }}</li>
    </ul>

    <p><a href="{{ route('home') }}">Voltar</a></p>
</body>
</html>
