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
                    'temperature' => 0.6,
                    'max_tokens' => 300,
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
        $leadNome = data_get($contexto, 'nome', 'Cliente');
        $cidade = data_get($contexto, 'cidade', 'não informada');
        $bairro = data_get($contexto, 'bairro', 'não informado');
        $interesse = data_get($contexto, 'interesse', 'não informado');
        $urgencia = data_get($contexto, 'urgencia', 'não informada');
        $temperatura = data_get($contexto, 'temperatura', 'frio');
        $tipoImovel = data_get($contexto, 'tipo_imovel', 'não informado');
        $tipoProjeto = data_get($contexto, 'tipo_projeto', 'não informado');
        $largura = data_get($contexto, 'largura', 'não informada');
        $comprimento = data_get($contexto, 'comprimento', 'não informado');
        $estruturaExistente = data_get($contexto, 'estrutura_existente', 'não informada');
        $materialDesejado = data_get($contexto, 'material_desejado', 'não informado');

        return "
Você é o Lead360 AI da Baumann Envidraçamento.

Você é um consultor comercial experiente.
Responda de forma natural, curta e profissional.

REGRAS PRINCIPAIS
- Nunca responda tudo de uma vez
- Faça uma pergunta por vez
- Não repita perguntas já respondidas
- Use os fatos confirmados abaixo como verdade
- Se um dado já estiver confirmado, não peça de novo
- Conduza a conversa para orçamento

POSICIONAMENTO
- A Baumann não compete por preço
- Valoriza acabamento, durabilidade e segurança
- Fale como empresa séria e consultiva

FATOS JÁ CONFIRMADOS
Nome: {$leadNome}
Cidade: {$cidade}
Bairro: {$bairro}
Tipo de imóvel: {$tipoImovel}
Tipo de projeto: {$tipoProjeto}
Largura: {$largura}
Comprimento: {$comprimento}
Estrutura existente: {$estruturaExistente}
Material desejado: {$materialDesejado}
Interesse: {$interesse}
Urgência: {$urgencia}
Temperatura: {$temperatura}

COMPORTAMENTO
- Se o lead está frio, descubra mais
- Se está morno, avance
- Se está quente, encaminhe para orçamento
- Se já há medida informada, não pergunte medida de novo
- Se já há estrutura informada, não pergunte estrutura de novo
- Se já há tipo de imóvel informado, não pergunte tipo de imóvel de novo

OBJETIVO FINAL
Levar o cliente para:
- envio de fotos
- envio de medidas, se faltarem
- ou avanço para orçamento
";
    }
}
