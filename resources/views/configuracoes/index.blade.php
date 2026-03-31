<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Lead360 AI</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f7fb;
            margin: 0;
            padding: 0;
            color: #1f2937;
        }

        .container {
            max-width: 1100px;
            margin: 40px auto;
            padding: 24px;
        }

        .topbar {
            margin-bottom: 20px;
        }

        .topbar a {
            text-decoration: none;
            color: #111827;
            font-weight: bold;
        }

        .card {
            background: #ffffff;
            border-radius: 14px;
            padding: 28px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        }

        h1 {
            margin-top: 0;
        }

        .list {
            margin-top: 20px;
        }

        .item {
            padding: 12px 14px;
            border-bottom: 1px solid #e5e7eb;
        }

        .item strong {
            display: block;
            margin-bottom: 4px;
        }

        .note {
            margin-top: 20px;
            padding: 14px 16px;
            background: #ecfeff;
            border-radius: 10px;
            color: #155e75;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <a href="{{ route('home') }}">← Voltar para início</a>
        </div>

        <div class="card">
            <h1>Configurações</h1>
            <p>Área inicial para parâmetros do sistema.</p>

            <div class="list">
                <div class="item">
                    <strong>OpenAI</strong>
                    Configuração futura da integração com inteligência artificial.
                </div>

                <div class="item">
                    <strong>WhatsApp</strong>
                    Configuração futura do canal de atendimento.
                </div>

                <div class="item">
                    <strong>Kommo</strong>
                    Configuração futura do CRM.
                </div>

                <div class="item">
                    <strong>Prompt e regras</strong>
                    Ajustes futuros do comportamento do atendimento.
                </div>
            </div>

            <div class="note">
                Esta tela será usada depois para configurar o comportamento do Lead360 AI.
            </div>
        </div>
    </div>
</body>
</html>
