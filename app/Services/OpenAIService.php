<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function responderLead(string $mensagem, array $contexto = []): string
    {
        $guardrails = app(Lead360GuardrailsService::class);

        if (! $guardrails->atendimentoIaHabilitado()) {
            return $guardrails->mensagemAtendimentoDesabilitado();
        }

        if (! $guardrails->openAiHabilitada()) {
            return 'A integração com IA está desativada no momento.';
        }

        if (! $guardrails->produtoPermitido($mensagem)) {
            return $guardrails->mensagemProdutoForaEscopo();
        }

        $apiKey = env('OPENAI_API_KEY');

        if (! $apiKey) {
            return 'OpenAI não configurada.';
        }

        $historico = data_get($contexto, 'historico', []);
        $messages = [];

        $messages[] = [
            'role' => 'system',
            'content' => $this->montarPromptSistema($contexto),
        ];

        foreach ($historico as $item) {
            if (! empty($item['pergunta'])) {
                $messages[] = [
                    'role' => 'user',
                    'content' => $item['pergunta'],
                ];
            }

            if (! empty($item['resposta'])) {
                $messages[] = [
                    'role' => 'assistant',
                    'content' => $item['resposta'],
                ];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => $mensagem,
        ];

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                    'messages' => $messages,
                    'temperature' => 0.4,
                    'max_tokens' => 350,
                ]);

            if (! $response->successful()) {
                return 'Erro OpenAI: ' . $response->body();
            }

            return data_get(
                $response->json(),
                'choices.0.message.content',
                'Sem resposta.'
            );
        } catch (Throwable $e) {
            return 'Erro interno ao consultar a OpenAI: ' . $e->getMessage();
        }
    }

    protected function montarPromptSistema(array $contexto = []): string
    {
        $guardrails = app(Lead360GuardrailsService::class);

        $nome = data_get($contexto, 'nome', 'Cliente');
        $cidade = data_get($contexto, 'cidade', 'não informada');
        $bairro = data_get($contexto, 'bairro', 'não informado');
        $tipoImovel = data_get($contexto, 'tipo_imovel', 'não informado');
        $tipoProjeto = data_get($contexto, 'tipo_projeto', 'não informado');
        $largura = data_get($contexto, 'largura', 'não informada');
        $comprimento = data_get($contexto, 'comprimento', 'não informado');
        $estruturaExistente = data_get($contexto, 'estrutura_existente', 'não informada');
        $materialDesejado = data_get($contexto, 'material_desejado', 'não informado');
        $interesse = data_get($contexto, 'interesse', 'não informado');
        $temperatura = data_get($contexto, 'temperatura', 'frio');
        $perfilCliente = data_get($contexto, 'perfil_cliente', 'comum');
        $faseFunil = data_get($contexto, 'fase_funil', 'entrada');
        $urgenciaReal = data_get($contexto, 'urgencia_real', 'media');
        $preferenciaEstetica = data_get($contexto, 'preferencia_estetica', 'não informada');
        $objecaoPrincipal = data_get($contexto, 'objecao_principal', 'não informada');
        $medoPrincipal = data_get($contexto, 'medo_principal', 'não informado');
        $motivoCompra = data_get($contexto, 'motivo_compra', 'não informado');
        $restricaoOrcamento = data_get($contexto, 'restricao_orcamento', 'não informada');
        $restricaoPrazo = data_get($contexto, 'restricao_prazo', 'não informada');
        $proximaAcao = data_get($contexto, 'proxima_acao', 'não definida');
        $resumoContexto = data_get($contexto, 'resumo_contexto', 'sem resumo');

        $podeAgendar = $guardrails->iaPodeAgendarDiretamente() ? 'sim' : 'não';
        $podeOrcar = $guardrails->iaPodeGerarOrcamentoDiretamente() ? 'sim' : 'não';
        $podePrecoSemContexto = $guardrails->iaPodeFalarPrecoSemContexto() ? 'sim' : 'não';
        $fluxoGuiado = $guardrails->fluxoGuiadoAtivo() ? 'sim' : 'não';

        return "
Você é o Lead360 AI da Baumann Envidraçamento.

Seu papel é atuar como consultor comercial organizado, seguro e objetivo.

REGRAS OBRIGATÓRIAS
- Nunca invente produto fora do escopo da Baumann.
- Não diga que trabalha com toldo, telha de barro, madeira ou itens fora da linha.
- Nunca agende diretamente se a regra disser que não pode.
- Nunca confirme visita, reunião ou horário como se já estivesse marcado.
- Quando não puder agendar, diga que vai encaminhar para o time responsável verificar disponibilidade.
- Nunca fale preço cedo demais se ainda faltar contexto.
- Nunca repita perguntas já respondidas.
- Nunca trate cliente técnico como leigo.
- Sempre que faltar contexto mínimo, faça a próxima pergunta certa.
FORMA DE FALAR (MUITO IMPORTANTE)
- Fale como um consultor experiente, não como robô
- Seja natural, educado e direto
- Use linguagem simples e humana
- Evite respostas duras ou frias
- Pode usar leve simpatia (ex: "perfeito", "entendi", "legal")
- Nunca use emojis em excesso (no máximo 1 leve quando fizer sentido)
- Evite parecer interrogatório
- Sempre conecte a pergunta com contexto

ESTILO COMERCIAL
- Você não é um atendente, é um especialista
- Não empurre venda, conduza
- Mostre segurança e domínio
- Evite falar de preço cedo
- Foque em entender antes de oferecer

ESCOPO BAUMANN
- coberturas em vidro
- coberturas em policarbonato
- envidraçamentos e soluções relacionadas
- estruturas dentro da linha da empresa

COMPORTAMENTO CONFIGURADO
- Pode agendar diretamente? {$podeAgendar}
- Pode gerar orçamento diretamente? {$podeOrcar}
- Pode falar de preço sem contexto? {$podePrecoSemContexto}
- Fluxo guiado ativo? {$fluxoGuiado}

SE O CLIENTE PEDIR ALGO FORA DO ESCOPO
- responda com elegância
- diga que não faz parte da linha atual
- ofereça continuar ajudando dentro das soluções da empresa

SE O PERFIL FOR TECNICO OU ARQUITETO
- seja mais direto
- menos enrolação
- mais clareza técnica
- mais objetividade

SE A FASE FOR PROPOSTA OU NEGOCIACAO
- não baixe preço automaticamente
- defenda valor
- explique limites técnicos
- mostre próxima ação

SE A FASE FOR OBRA OU ASSISTENCIA
- não agende diretamente se a configuração não permitir
- peça os detalhes mínimos
- diga que vai encaminhar ao setor responsável

FATOS CONFIRMADOS
Nome: {$nome}
Cidade: {$cidade}
Bairro: {$bairro}
Tipo de imóvel: {$tipoImovel}
Tipo de projeto: {$tipoProjeto}
Largura: {$largura}
Comprimento: {$comprimento}
Estrutura existente: {$estruturaExistente}
Material desejado: {$materialDesejado}
Interesse: {$interesse}
Temperatura: {$temperatura}
Perfil do cliente: {$perfilCliente}
Fase do funil: {$faseFunil}
Urgência real: {$urgenciaReal}
Preferência estética: {$preferenciaEstetica}
Objeção principal: {$objecaoPrincipal}
Medo principal: {$medoPrincipal}
Motivo da compra: {$motivoCompra}
Restrição de orçamento: {$restricaoOrcamento}
Restrição de prazo: {$restricaoPrazo}
Próxima ação: {$proximaAcao}
Resumo do contexto: {$resumoContexto}
";
    }
}
