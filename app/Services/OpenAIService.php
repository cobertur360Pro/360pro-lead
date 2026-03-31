<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OpenAIService
{
    public function responderLead(string $mensagem, array $contexto = []): string
    {
        $apiKey = env('OPENAI_API_KEY');

        if (! $apiKey) {
            return 'OpenAI não configurada. Cadastre a OPENAI_API_KEY no ambiente.';
        }

        $promptSistema = $this->montarPromptSistema($contexto);

        $response = Http::withToken($apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $promptSistema,
                    ],
                    [
                        'role' => 'user',
                        'content' => $mensagem,
                    ],
                ],
                'temperature' => 0.7,
            ]);

        if (! $response->successful()) {
            return 'Erro ao consultar a OpenAI: ' . $response->body();
        }

        return data_get($response->json(), 'choices.0.message.content', 'Sem resposta da IA.');
    }

    protected function montarPromptSistema(array $contexto = []): string
    {
        $leadNome = data_get($contexto, 'nome', 'Cliente');
        $cidade = data_get($contexto, 'cidade', 'não informada');
        $interesse = data_get($contexto, 'interesse', 'não informado');
        $urgencia = data_get($contexto, 'urgencia', 'não informada');
        $temperatura = data_get($contexto, 'temperatura', 'frio');

        return "Você é o Lead360 AI, assistente comercial consultivo da Baumann. ".
            "Seu papel é responder com clareza, educação e objetividade. ".
            "Ajude a qualificar o lead e a avançar o atendimento. ".
            "Nunca invente política comercial, contrato ou desconto. ".
            "Se faltar informação, peça de forma simples e natural. ".
            "Contexto atual do lead: ".
            "Nome: {$leadNome}. ".
            "Cidade: {$cidade}. ".
            "Interesse: {$interesse}. ".
            "Urgência: {$urgencia}. ".
            "Temperatura: {$temperatura}.";
    }
}
