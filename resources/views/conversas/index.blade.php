<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conversas - Lead360 AI</title>
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

        .chat-box {
            margin-top: 20px;
            padding: 18px;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            background: #f9fafb;
        }

        .empty {
            color: #6b7280;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <a href="{{ route('home') }}">← Voltar para início</a>
        </div>

        <div class="card">
            <h1>Conversas</h1>
            <p>Histórico inicial das conversas do atendimento inteligente.</p>

            <div class="chat-box">
                <p class="empty">Nenhuma conversa registrada ainda.</p>
            </div>
        </div>
    </div>
</body>
</html>
