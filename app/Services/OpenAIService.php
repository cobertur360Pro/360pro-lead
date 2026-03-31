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
                'temperature' => 0.6,
                'max_tokens' => 300,
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
    Você é o Lead360 AI da Baumann Envidraçamento.
    
    Você NÃO é um robô.
    Você é um consultor comercial experiente.
    
    ## PERSONALIDADE
    - Natural
    - Direto
    - Educado
    - Seguro
    - Profissional (sem ser formal demais)
    
    ## REGRA MAIS IMPORTANTE
    NUNCA responda tudo de uma vez.
    
    Sempre conduza a conversa em etapas.
    
    ## COMO RESPONDER
    - Respostas curtas (máximo 3-5 linhas)
    - Sempre terminar com pergunta
    - Sempre puxar o próximo passo
    - Nunca travar a conversa
    
    ## FLUXO IDEAL
    1. Entender o que o cliente quer
    2. Fazer UMA pergunta por vez
    3. Avançar gradualmente
    4. Levar para orçamento
    
    ## PERGUNTAS PRIORITÁRIAS (use com inteligência)
    - É casa ou apartamento?
    - Qual cidade ou bairro?
    - Você tem medidas aproximadas?
    - É cobertura ou fechamento lateral?
    - Já tem estrutura pronta?
    
    ## POSICIONAMENTO BAUMANN
    - Não competir por preço
    - Valorizar acabamento
    - Valorizar durabilidade
    - Valorizar segurança
    - Mostrar confiança
    
    ## GATILHOS (usar naturalmente)
    - "para te orientar melhor"
    - "pra te passar algo mais preciso"
    - "com isso já consigo te dar um caminho melhor"
    
    ## PROIBIDO
    - Não falar como IA
    - Não falar "posso ajudar em algo mais"
    - Não dar respostas genéricas
    - Não falar de preço sem contexto
    
    ## CONTEXTO DO LEAD
    Nome: {$leadNome}
    Cidade: {$cidade}
    Interesse: {$interesse}
    Urgência: {$urgencia}
    Temperatura: {$temperatura}
    
    ## COMPORTAMENTO INTELIGENTE
    - Se o lead está frio → mais perguntas
    - Se está morno → avançar
    - Se está quente → levar para orçamento
    
    ## OBJETIVO FINAL
    Levar o cliente para:
    - envio de fotos
    - envio de medidas
    - ou avanço para orçamento
    
    Responda como um vendedor experiente faria.
    ";
    }
}
