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

    <h2>Qualificação comercial</h2>

    <form method="POST" action="{{ route('leads.qualificacao', $lead->id) }}">
        @csrf

        <label>Origem:</label>
        <select name="origem">
            <option value="">Selecione</option>
            <option value="site" {{ $lead->origem === 'site' ? 'selected' : '' }}>Site</option>
            <option value="whatsapp" {{ $lead->origem === 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
            <option value="indicacao" {{ $lead->origem === 'indicacao' ? 'selected' : '' }}>Indicação</option>
            <option value="instagram" {{ $lead->origem === 'instagram' ? 'selected' : '' }}>Instagram</option>
        </select>

        <br><br>

        <label>Interesse:</label>
        <input type="text" name="interesse" placeholder="Ex: cobertura, sacada..." value="{{ $lead->interesse }}">

        <br><br>

        <label>Urgência:</label>
        <select name="urgencia">
            <option value="">Selecione</option>
            <option value="baixa" {{ $lead->urgencia === 'baixa' ? 'selected' : '' }}>Baixa</option>
            <option value="media" {{ $lead->urgencia === 'media' ? 'selected' : '' }}>Média</option>
            <option value="alta" {{ $lead->urgencia === 'alta' ? 'selected' : '' }}>Alta</option>
        </select>

        <br><br>

        <button type="submit">Salvar qualificação</button>
    </form>

    <hr>

    <h3>Resumo comercial</h3>

    <p><strong>Origem:</strong> {{ $lead->origem }}</p>
    <p><strong>Interesse:</strong> {{ $lead->interesse }}</p>
    <p><strong>Urgência:</strong> {{ $lead->urgencia }}</p>
    <p><strong>Temperatura:</strong> {{ $lead->temperatura }}</p>
    <p><strong>Score:</strong> {{ $lead->score }}</p>
    
    <hr>
    
    <h3>Memória estruturada</h3>
    <p><strong>Bairro:</strong> {{ $lead->bairro }}</p>
    <p><strong>Tipo de imóvel:</strong> {{ $lead->tipo_imovel }}</p>
    <p><strong>Tipo de projeto:</strong> {{ $lead->tipo_projeto }}</p>
    <p><strong>Largura:</strong> {{ $lead->largura }}</p>
    <p><strong>Comprimento:</strong> {{ $lead->comprimento }}</p>
    <p><strong>Estrutura existente:</strong> {{ $lead->estrutura_existente }}</p>
    <p><strong>Material desejado:</strong> {{ $lead->material_desejado }}</p>

    <hr>

    <h3>Cérebro comercial</h3>
    <p><strong>Perfil do cliente:</strong> {{ $lead->perfil_cliente }}</p>
    <p><strong>Fase do funil:</strong> {{ $lead->fase_funil }}</p>
    <p><strong>Urgência real:</strong> {{ $lead->urgencia_real }}</p>
    <p><strong>Preferência estética:</strong> {{ $lead->preferencia_estetica }}</p>
    <p><strong>Objeção principal:</strong> {{ $lead->objecao_principal }}</p>
    <p><strong>Medo principal:</strong> {{ $lead->medo_principal }}</p>
    <p><strong>Motivo da compra:</strong> {{ $lead->motivo_compra }}</p>
    <p><strong>Restrição de orçamento:</strong> {{ $lead->restricao_orcamento }}</p>
    <p><strong>Restrição de prazo:</strong> {{ $lead->restricao_prazo }}</p>
    <p><strong>Cliente técnico:</strong> {{ $lead->cliente_tecnico ? 'Sim' : 'Não' }}</p>
    <p><strong>Cliente existente:</strong> {{ $lead->cliente_existente ? 'Sim' : 'Não' }}</p>
    <p><strong>Próxima ação:</strong> {{ $lead->proxima_acao }}</p>
    <p><strong>Data de follow-up:</strong> {{ optional($lead->data_followup)->format('d/m/Y') }}</p>
    <p><strong>Resumo do contexto:</strong> {{ $lead->resumo_contexto }}</p>
    
    <h2>Observações</h2>
    <form method="POST" action="{{ route('leads.observacoes', $lead->id) }}">
        @csrf
        <textarea name="observacoes" rows="6" cols="80" placeholder="Digite observações sobre este lead...">{{ $lead->observacoes }}</textarea>
        <br><br>
        <button type="submit">Salvar observações</button>
    </form>

    <hr>

    <h2>Nova interação manual</h2>
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

    <h2>Teste da IA</h2>
    <form method="POST" action="{{ route('leads.ia', $lead->id) }}">
        @csrf
        <textarea name="mensagem_ia" rows="5" cols="80" placeholder="Digite aqui uma mensagem como se fosse o cliente..." required></textarea>
        <br><br>
        <button type="submit">Perguntar para a IA</button>
    </form>

    <hr>

    <h2>Histórico de interações</h2>

    @forelse($lead->interactions as $interaction)
        <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
            <p><strong>Tipo:</strong> {{ $interaction->tipo }}</p>
            <p><strong>Data:</strong> {{ $interaction->created_at }}</p>
            <p><strong>Conteúdo:</strong><br>{{ $interaction->conteudo }}</p>

            @if($interaction->resposta_ia)
                <hr>
                <p><strong>Resposta da IA:</strong><br>{{ $interaction->resposta_ia }}</p>
            @endif
        </div>
    @empty
        <p>Nenhuma interação registrada ainda.</p>
    @endforelse

    <br>
    <a href="{{ route('leads.index') }}">Voltar para leads</a>
</body>
</html>
