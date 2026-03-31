<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leads - Lead360 AI</title>
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: 12px;
            text-align: left;
        }

        th {
            background: #f9fafb;
        }

        .empty {
            margin-top: 20px;
            padding: 14px 16px;
            background: #fff7ed;
            border-radius: 10px;
            color: #9a3412;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="topbar">
            <a href="{{ route('home') }}">← Voltar para início</a>
        </div>

        <div class="card">
            <h1>Leads</h1>
            <p>Lista inicial de leads do sistema.</p>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>Cidade</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="5">Nenhum lead cadastrado ainda.</td>
                    </tr>
                </tbody>
            </table>

            <div class="empty">
                Quando conectarmos o banco, os leads reais aparecerão aqui.
            </div>
        </div>
    </div>
</body>
</html>
