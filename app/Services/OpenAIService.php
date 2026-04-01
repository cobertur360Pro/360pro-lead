<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function processarTurnoEstruturado(string $mensagem, array $contexto = []): ?array
    {
        $apiKey = env('OPENAI_API_KEY');

        if (! $apiKey) {
            return null;
        }

        $prompt = $this->montarPromptTurnoEstruturado($contexto);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $mensagem],
                    ],
                    'temperature' => 0.2,
                    'max_tokens' => 600,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (! is_string($content)) {
                return null;
            }

            $json = $this->extractJson($content);

            return $json ? json_decode($json, true) : null;

        } catch (Throwable $e) {
            return null;
        }
    }

    protected function montarPromptTurnoEstruturado(array $contexto = []): string
    {
        return "
Você é o cérebro comercial da Baumann Envidraçamento.

Sua tarefa:
- entender a mensagem do cliente
- extrair dados relevantes
- decidir o próximo passo
- responder como um consultor humano

REGRAS IMPORTANTES:
- não seja robô
- não repita perguntas já respondidas
- entenda o sentido (mesmo com erro de escrita)
- se o cliente pedir visita → priorize isso
- nome é obrigatório e deve ser buscado cedo
- não faça interrogatório
- não ignore o contexto

CONTEXTO ATUAL:
Nome: " . ($contexto['nome'] ?? 'não informado') . "
Cidade: " . ($contexto['cidade'] ?? 'não informado') . "
Bairro: " . ($contexto['bairro'] ?? 'não informado') . "
Tipo de projeto: " . ($contexto['solucao_principal'] ?? 'não informado') . "
Área: " . ($contexto['area_projeto'] ?? 'não informado') . "

RESPONDA EM JSON PURO:

{
  \"understood_summary\": \"\",
  \"answered_current_gap\": true,
  \"extracted\": {
    \"nome\": null,
    \"solucao_principal\": null,
    \"solucao_subtipo\": null,
    \"tipo_imovel\": null,
    \"area_projeto\": null,
    \"largura\": null,
    \"comprimento\": null,
    \"principal_desejo\": null,
    \"quer_visita\": false
  },
  \"decision\": {
    \"action\": \"perguntar\",
    \"next_gap\": null,
    \"reason\": \"\"
  },
  \"reply\": \"\"
}
";
    }

    protected function extractJson(string $text): ?string
    {
        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false) {
            return null;
        }

        return substr($text, $start, $end - $start + 1);
    }
}
