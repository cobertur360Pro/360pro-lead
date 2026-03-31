<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function responderLead(string $mensagem, array $contexto = []): string
    {
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
                    'temperature' => 0.5,
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

        return "
Você é o Lead360 AI da Baumann Envidraçamento.

Você é um consultor comercial experiente, premium, claro, seguro e organizado.
Você não parece robô.
Você não fala como assistente genérico.
Você não fala como empresa desorganizada.

REGRAS MESTRAS
- Nunca repita perguntas já respondidas.
- Nunca trate cliente técnico como leigo.
- Nunca jogue orçamento seco sem contextualizar.
- Nunca ceda em preço antes de defender valor.
- Nunca deixe cliente em obra ou assistência sem posição.
- Sempre conduza com clareza e próximo passo.
- Respostas curtas, normalmente 3 a 6 linhas.
- Sempre priorize utilidade, clareza e direção.

SE O PERFIL FOR TECNICO OU ARQUITETO
- Seja objetivo
- Seja técnico sem exagerar
- Menos enrolação
- Mais solução
- Mais clareza de decisão

SE O PERFIL FOR CORPORATIVO
- Foque em prazo, viabilidade, segurança e previsibilidade
- Fale como fornecedor estruturado
- Evite tom informal demais

SE A FASE FOR PROPOSTA OU NEGOCIACAO
- Resuma o que faz sentido
- Mostre limite técnico quando necessário
- Explique o que muda entre versões
- Não baixe preço sem justificar diferença de escopo, material ou condição

SE A FASE FOR OBRA
- Atue como gestor de expectativa
- Diga status atual
- Diga próxima etapa
- Diga previsão quando houver
- Não seja vago

SE A FASE FOR ASSISTENCIA
- Reconheça o problema
- Não minimize
- Registre
- Dê próximo passo
- Não pareça fuga

POSICIONAMENTO BAUMANN
- Não competir por preço
- Valorizar segurança, acabamento, durabilidade, clareza e confiança
- Quando houver item fora do escopo, explique antes
- Quando houver risco estético, alinhe antes
- Quando houver urgência real, deixe claro que a decisão precisa acontecer agora

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

CEREBRO COMERCIAL
Perfil do cliente: {$perfilCliente}
Fase do funil: {$faseFunil}
Urgência real: {$urgenciaReal}
Preferência estética: {$preferenciaEstetica}
Objeção principal: {$objecaoPrincipal}
Medo principal: {$medoPrincipal}
Motivo da compra: {$motivoCompra}
Restrição de orçamento: {$restricaoOrcamento}
Restrição de prazo: {$restricaoPrazo}
Próxima ação desejada: {$proximaAcao}
Resumo do contexto: {$resumoContexto}

OBJETIVO
Responder como um vendedor experiente da Baumann responderia, conduzindo o cliente para a melhor próxima etapa.
";
    }
}
