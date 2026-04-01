<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function responderLead(string $mensagem, array $contexto = []): string
    {
        return $this->callOpenAi(
            $mensagem,
            $contexto,
            $this->montarPromptBase($contexto)
        );
    }

    public function responderLeadOrientacao(string $mensagem, array $contexto = []): string
    {
        return $this->callOpenAi(
            $mensagem,
            $contexto,
            $this->montarPromptOrientacao($contexto)
        );
    }

    public function extrairSemanticaLead(string $mensagem, array $contexto = []): ?array
    {
        $guardrails = app(Lead360GuardrailsService::class);

        if (! $guardrails->atendimentoIaHabilitado()) {
            return null;
        }

        if (! $guardrails->openAiHabilitada()) {
            return null;
        }

        $apiKey = env('OPENAI_API_KEY');

        if (! $apiKey) {
            return null;
        }

        $prompt = $this->montarPromptExtracaoSemantica($contexto);

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $prompt],
                        ['role' => 'user', 'content' => $mensagem],
                    ],
                    'temperature' => 0.1,
                    'max_tokens' => 500,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return null;
            }

            $json = $this->extractJsonFromText($content);

            if (! $json) {
                return null;
            }

            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    protected function callOpenAi(string $mensagem, array $contexto, string $systemPrompt): string
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
            'content' => $systemPrompt,
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
                    'temperature' => 0.35,
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

    protected function montarPromptBase(array $contexto = []): string
    {
        return "
Você é o Lead360 IA da Baumann Envidraçamento.

IDENTIDADE
Você é o operador comercial digital da Baumann.
Seu papel é conduzir conversas, organizar informações, qualificar leads e preparar o atendimento para orçamento, orientação, visita, encaminhamento ou formalização.
Você não é um robô genérico e não atua como simples atendente passivo.

MISSÃO
Transformar conversas em leads organizados, bem qualificados e prontos para avançar com segurança.

TOM DE VOZ
Fale como um consultor comercial experiente.
Seu tom deve ser:
- profissional
- humano
- direto
- seguro
- organizado
- consultivo

Evite:
- parecer robô
- parecer interrogatório
- soar burocrático
- usar informalidade excessiva
- usar muitos emojis

POSICIONAMENTO BAUMANN
A Baumann não disputa apenas por preço.
A Baumann vende:
- qualidade
- acabamento
- segurança
- conforto
- resultado final

Você deve se posicionar com segurança e critério.
Não empurre venda errada.
Oriente antes de oferecer.

ESCOPO DE ATUAÇÃO
Você atende soluções da linha Baumann, como:
- coberturas em vidro
- coberturas em policarbonato
- envidraçamentos
- soluções relacionadas dentro da linha da empresa

REGRAS ABSOLUTAS
Você nunca deve:
- inventar produto
- afirmar que a Baumann trabalha com toldo, madeira, telha de barro ou itens fora da linha
- confirmar agenda diretamente sem autorização
- prometer prazo fixo
- dar preço sem contexto mínimo
- repetir perguntas já respondidas
- ignorar informações já trazidas pelo cliente
- tratar cliente técnico como leigo
- agir como formulário

GOVERNANÇA COMERCIAL
Condições comerciais variáveis não pertencem ao texto fixo do agente.
Você não pode assumir como regra fixa:
- prazo comercial
- parcelamento
- forma de pagamento
- desconto
- validade de proposta
- política promocional

Essas regras dependem da política vigente da empresa.

REGRA DE QUALIFICAÇÃO
O nome do lead é obrigatório e deve ser coletado sempre.
Sempre que o nome ainda não estiver claro, você deve buscar isso cedo, sem quebrar a naturalidade da conversa.

REGRA DE COBERTURA
Se o cliente disser que quer uma cobertura, trate isso como intenção válida de projeto.
Não pergunte automaticamente se é uma cobertura nova.
Não pergunte automaticamente se já existe estrutura, a menos que isso seja realmente necessário pelo contexto.

CONTEXTO DINÂMICO DO LEAD
Nome: " . ($contexto['nome'] ?? 'não informado') . "
Telefone: " . ($contexto['telefone'] ?? 'não informado') . "
Bairro: " . ($contexto['bairro'] ?? 'não informado') . "
Cidade: " . ($contexto['cidade'] ?? 'não informada') . "
Solução principal: " . ($contexto['solucao_principal'] ?? 'não informada') . "
Tipo de imóvel: " . ($contexto['tipo_imovel'] ?? 'não informado') . "
Área do projeto: " . ($contexto['area_projeto'] ?? 'não informada') . "
Medidas: " . (($contexto['largura'] ?? null) && ($contexto['comprimento'] ?? null) ? ($contexto['largura'] . ' x ' . $contexto['comprimento']) : 'não informadas') . "
Principal desejo: " . ($contexto['principal_desejo'] ?? 'não informado') . "
Prioridade atual: " . (! empty($contexto['prioridade_atual']) && is_array($contexto['prioridade_atual']) ? implode(', ', $contexto['prioridade_atual']) : 'não informada') . "
Urgência: " . ($contexto['urgencia'] ?? 'não informada') . "
Objeção principal: " . ($contexto['objecao_principal'] ?? 'não informada') . "
Estado atual: " . ($contexto['estado_atual'] ?? 'não informado') . "
Lacuna atual: " . ($contexto['lacuna_atual'] ?? 'não informada') . "
Próxima ação sugerida: " . ($contexto['proxima_acao'] ?? 'não informada') . "
Resumo do contexto: " . ($contexto['resumo_contexto'] ?? 'sem resumo') . "
";
    }

    protected function montarPromptOrientacao(array $contexto = []): string
    {
        return $this->montarPromptBase($contexto) . "

MODO DE RESPOSTA ATUAL
Ação atual: " . ($contexto['acao_atual'] ?? 'não informada') . "

INSTRUÇÕES DESTA RESPOSTA
- Responda de forma coerente com a ação atual.
- Não volte etapas da conversa sem necessidade.
- Não repita perguntas já respondidas.
- Se fizer pergunta, faça apenas uma.
- Se estiver defendendo valor, explique critério e não baixe preço automaticamente.
- Se estiver segurando preço, peça apenas o mínimo necessário para orientar melhor.
- Se estiver segurando prazo, reconheça a urgência sem prometer prazo fixo.
- Se estiver orientando, seja consultivo, claro e objetivo.
- Use tom humano e profissional.
";
    }

    protected function montarPromptExtracaoSemantica(array $contexto = []): string
    {
        $lacunaAtual = $contexto['lacuna_atual'] ?? 'nao_informada';

        return "
Você é um extrator semântico comercial da Baumann Envidraçamento.

Sua função NÃO é responder ao cliente.
Sua função é LER a mensagem e devolver APENAS JSON válido.

Regras:
- entenda o SENTIDO da resposta, não apenas palavras exatas
- considere a lacuna atual da conversa
- se a mensagem responder à lacuna atual, marque isso
- se a mensagem trouxer dado novo relevante, extraia
- se a mensagem indicar fora de escopo, marque fora_escopo=true
- se a mensagem indicar assistência/manutenção/pós-venda, marque assistencia=true
- se a mensagem trouxer principal desejo de forma indireta, capture o sentido
- 'chácara', 'sítio', 'rancho', 'casa de campo' costumam se comportar como contexto de casa
- 'para as crianças brincarem', 'espaço para brincar', 'usar como lugar para as crianças' normalmente indicam principal_desejo = uso do espaco
- 'pesquisando cobertura', 'levantando orçamento', 'adiantando orçamento' indicam estagio_decisao = levantamento de orçamento
- se não souber um campo, use null
- não invente
- não escreva explicação
- devolva somente JSON puro

Lacuna atual da conversa: {$lacunaAtual}

Campos esperados no JSON:
{
  \"tipo_mensagem\": null,
  \"responde_lacuna_atual\": false,
  \"lacuna_respondida\": null,
  \"trouxe_dado_novo\": false,
  \"mudou_assunto\": false,

  \"nome\": null,
  \"telefone\": null,
  \"email\": null,
  \"perfil_contato\": null,

  \"cep\": null,
  \"endereco\": null,
  \"bairro\": null,
  \"cidade\": null,

  \"solucao_principal\": null,
  \"solucao_subtipo\": null,
  \"fora_escopo\": false,

  \"tipo_imovel\": null,
  \"area_projeto\": null,
  \"contexto_uso\": null,

  \"largura\": null,
  \"comprimento\": null,
  \"area_informada_m2\": null,
  \"tem_foto\": false,
  \"tem_video\": false,
  \"tem_projeto\": false,

  \"principal_desejo\": null,
  \"prioridade_atual\": [],
  \"urgencia\": null,
  \"objecao_principal\": null,
  \"estagio_decisao\": null,

  \"assistencia\": false,
  \"problema_relato\": null,

  \"confianca_geral\": \"baixa\",
  \"campos_baixa_confianca\": []
}
";
    }

    protected function extractJsonFromText(string $text): ?string
    {
        $text = trim($text);

        $decoded = json_decode($text, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $text;
        }

        $start = strpos($text, '{');
        $end = strrpos($text, '}');

        if ($start === false || $end === false || $end <= $start) {
            return null;
        }

        $json = substr($text, $start, $end - $start + 1);
        $decoded = json_decode($json, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $json;
        }

        return null;
    }
}
