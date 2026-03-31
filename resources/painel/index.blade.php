<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel - Lead360 AI</title>
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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 24px;
        }

        .box {
            background: #111827;
            color: #ffffff;
            border-radius: 12px;
            padding: 20px;
        }

        .box h3 {
            margin: 0 0 10px;
            font-size: 15px;
        }

        .box strong {
            font-size: 28px;
        }

        .note {
            margin-top: 24px;
            background: #eff6ff;
            color: #1d4ed8;
            padding: 14px 16px;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <a href="{{ route('home') }}">← Voltar para início</a>
        </div>

        <div class="card">
            <h1>Painel Lead360 AI</h1>
            <p>Resumo inicial do sistema. Esta tela será a base do painel administrativo.</p>

            <div class="grid">
                <div class="box">
                    <h3>Leads</h3>
                    <strong>0</strong>
                </div>

                <div class="box">
                    <h3>Conversas</h3>
                    <strong>0</strong>
                </div>

                <div class="box">
                    <h3>Atendimentos ativos</h3>
                    <strong>0</strong>
                </div>

                <div class="box">
                    <h3>Prontos para orçamento</h3>
                    <strong>0</strong>
                </div>
            </div>

            <div class="note">
                Próximo passo: conectar banco e começar a cadastrar leads reais.
            </div>
        </div>
    </div>
</body>
</html>
