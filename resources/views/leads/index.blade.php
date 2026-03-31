<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Leads</title>
</head>
<body>

<h1>Leads</h1>

<form method="POST" action="/leads">
    @csrf
    <input type="text" name="nome" placeholder="Nome" required>
    <input type="text" name="telefone" placeholder="Telefone">
    <input type="text" name="cidade" placeholder="Cidade">
    <button type="submit">Salvar</button>
</form>

<hr>

<table border="1" cellpadding="10">
    <tr>
        <th>ID</th>
        <th>Nome</th>
        <th>Telefone</th>
        <th>Cidade</th>
        <th>Status</th>
    </tr>

    @foreach($leads as $lead)
    <tr>
        <td>{{ $lead->id }}</td>
        <td>{{ $lead->nome }}</td>
        <td>{{ $lead->telefone }}</td>
        <td>{{ $lead->cidade }}</td>
        <td>{{ $lead->status }}</td>
    </tr>
    @endforeach

</table>

<br>
<a href="/">Voltar</a>

</body>
</html>
