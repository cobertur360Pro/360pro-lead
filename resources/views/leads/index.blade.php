<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Lead360 AI</title>
</head>
<body>
    <h1>Leads</h1>

    <form method="POST" action="{{ route('leads.store') }}">
        @csrf
        <input type="text" name="nome" placeholder="Nome" required>
        <input type="text" name="telefone" placeholder="Telefone">
        <input type="text" name="cidade" placeholder="Cidade">
        <button type="submit">Salvar</button>
    </form>

    <hr>

    <table border="1" cellpadding="10">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome</th>
                <th>Telefone</th>
                <th>Cidade</th>
                <th>Status</th>
                <th>Detalhe</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td>{{ $lead->id }}</td>
                    <td>{{ $lead->nome }}</td>
                    <td>{{ $lead->telefone }}</td>
                    <td>{{ $lead->cidade }}</td>
                    <td>{{ $lead->status }}</td>
                    <td>
                        <a href="{{ route('leads.show', $lead->id) }}">Abrir</a>
                    </td>
                    <td>
                        <form method="POST" action="{{ route('leads.status', $lead->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit">Avançar</button>
                        </form>

                        <form method="POST" action="{{ route('leads.delete', $lead->id) }}" style="display:inline;">
                            @csrf
                            <button type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">Nenhum lead cadastrado ainda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <br>
    <a href="{{ route('home') }}">Voltar</a>
</body>
</html>
