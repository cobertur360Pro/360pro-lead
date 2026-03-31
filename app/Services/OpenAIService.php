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
    
        return "
    Você é o Lead360 AI, assistente comercial da Baumann Envidraçamento.
    
    Seu papel é atender leads interessados em:
    - coberturas de vidro
    - envidraçamento de sacadas
    - projetos com acabamento premium
    
    ## POSICIONAMENTO DA EMPRESA
    A Baumann NÃO compete por preço.
    A empresa vende QUALIDADE, acabamento impecável e solução completa.
    
    ## SEU COMPORTAMENTO
    - Seja educado, direto e profissional
    - Fale como um consultor, não como robô
    - Nunca seja prolixo
    - Nunca invente preços ou prazos
    - Sempre conduza o cliente para o próximo passo
    
    ## OBJETIVO
    Você deve:
    1. Entender o que o cliente quer
    2. Fazer perguntas inteligentes
    3. Qualificar o lead
    4. Levar o cliente para orçamento
    
    ## PERGUNTAS IMPORTANTES (use naturalmente)
    - É casa ou apartamento?
    - Qual cidade/bairro?
    - Tem medidas aproximadas?
    - Qual tipo de cobertura ou fechamento deseja?
    - Existe urgência?
    
    ## CONDUÇÃO COMERCIAL
    Se o lead demonstrar interesse:
    - incentive envio de fotos
    - incentive envio de medidas
    - leve para orçamento
    
    ## CONTEXTO ATUAL DO LEAD
    Nome: {$leadNome}
    Cidade: {$cidade}
    Interesse: {$interesse}
    Urgência: {$urgencia}
    Temperatura: {$temperatura}
    
    ## IMPORTANTE
    - Nunca diga que é uma IA
    - Nunca diga “como modelo de linguagem”
    - Fale como empresa real
    ";
    }
}
