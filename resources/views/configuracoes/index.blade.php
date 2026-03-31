<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Lead360 AI</title>
</head>
<body>
    <h1>Configurações</h1>

    <p>Para a IA funcionar, configure no Laravel Cloud a variável abaixo:</p>

    <pre>OPENAI_API_KEY=sua_chave_aqui</pre>

    <p>Opcional:</p>

    <pre>OPENAI_MODEL=gpt-4o-mini</pre>

    <p>Depois rode:</p>

    <pre>php artisan config:clear</pre>

    <br>
    <a href="{{ route('home') }}">Voltar</a>
</body>
</html>
