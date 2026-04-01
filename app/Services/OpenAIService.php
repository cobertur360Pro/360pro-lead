<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class OpenAIService
{
    public function processarTurnoEstruturado(string $mensagem, array $contexto = []): ?array
    {
        $guardrails = app(Lead360GuardrailsService::class);

        if (! $guardrails->atendimentoIaHabilitado()) {
            return null;
        }

        if (! $guardrails->openAiHabilitada()) {
            return null;
        }

        if (! $guardrails->produtoPermitido($mensagem)) {
            return [
                'understood_summary' => 'Cliente pediu item fora do escopo.',
                'answered_current_gap' => false,
                'extracted' => [
                    'nome' => null,
                    'telefone' => null,
                    'email' => null,
                    'perfil_contato' => null,
                    'cep' => null,
                    'endereco' => null,
                    'bairro' => null,
                    'cidade' => null,
                    'solucao_principal' => null,
                    'solucao_subtipo' => null,
                    'fora_escopo' => true,
                    'tipo_imovel' => null,
                    'area_projeto' => null,
                    'contexto_uso' => null,
                    'largura' => null,
                    'comprimento' => null,
                    'area_informada_m2' => null,
                    'tem_foto' => false,
                    'tem_video' => false,
                    'tem_projeto' => false,
                    'principal_desejo' => null,
                    'prioridade_atual' => [],
                    'urgencia' => null,
                    'objecao_principal' => null,
                    'estagio_decisao' => null,
                    'assistencia' => false,
                    'problema_relato' => null,
                    'quer_visita' => false,
                    'visit_refused' => false,
                ],
                'decision' => [
                    'action' => 'bloquear_fora_escopo',
                    'next_gap' => null,
                    'reason' => 'Pedido fora da linha Baumann.',
                ],
                'reply' => 'Hoje nosso atendimento está focado nas soluções da linha Baumann, como coberturas em vidro, policarbonato e envidraçamentos. Se o seu projeto estiver nessa linha, eu sigo com você por aqui.',
            ];
        }

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
                    'temperature' => 0.1,
                    'max_tokens' => 900,
                ]);

            if (! $response->successful()) {
                return null;
            }

            $content = data_get($response->json(), 'choices.0.message.content');

            if (! is_string($content) || trim($content) === '') {
                return null;
            }

            $json = $this->extractJson($content);

            if (! $json) {
                return null;
            }

            $decoded = json_decode($json, true);

            return is_array($decoded) ? $decoded : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    public function responderLead(string $mensagem, array $contexto = []): string
    {
        return $this->responderLivre($mensagem, $contexto, 'Responda como consultor comercial humano da Baumann.');
    }

    public function responderLeadOrientacao(string $mensagem, array $contexto = []): string
    {
        return $this->responderLivre($mensagem, $contexto, 'Responda como consultor comercial humano da Baumann, com foco em orientação clara e objetiva.');
    }

    protected function responderLivre(string $mensagem, array $contexto, string $instrucao): string
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

        try {
            $response = Http::withToken($apiKey)
                ->timeout(60)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
                    'messages' => [
                        ['role' => 'system', 'content' => $instrucao],
                        ['role' => 'user', 'content' => $mensagem],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 350,
                ]);

            if (! $response->successful()) {
                return 'Erro OpenAI: ' . $response->body();
            }

            return data_get($response->json(), 'choices.0.message.content', 'Sem resposta.');
        } catch (Throwable $e) {
            return 'Erro interno ao consultar a OpenAI: ' . $e->getMessage();
        }
    }

    protected function montarPromptTurnoEstruturado(array $contexto = []): string
    {
        $nome = $contexto['nome'] ?? 'não informado';
        $bairro = $contexto['bairro'] ?? 'não informado';
        $cidade = $contexto['cidade'] ?? 'não informado';
        $solucao = $contexto['solucao_principal'] ?? 'não informada';
        $tipoImovel = $contexto['tipo_imovel'] ?? 'não informado';
        $areaProjeto = $contexto['area_projeto'] ?? 'não informada';
        $largura = $contexto['largura'] ?? null;
        $comprimento = $contexto['comprimento'] ?? null;
        $principalDesejo = $contexto['principal_desejo'] ?? 'não informado';
        $prioridadeAtual = ! empty($contexto['prioridade_atual']) && is_array($contexto['prioridade_atual'])
            ? implode(', ', $contexto['prioridade_atual'])
            : 'não informada';
        $lacunaAtual = $contexto['lacuna_atual'] ?? 'não informada';
        $visitaRecusada = ! empty($contexto['visita_recusada']) ? 'sim' : 'não';
        $historico = $this->renderHistoricoResumo($contexto['historico'] ?? []);

        return "
Você é o cérebro comercial do Lead360 IA da Baumann Envidraçamento.

Sua tarefa, a cada mensagem:
1. entender o que o cliente quis dizer
2. extrair os dados comerciais úteis
3. decidir a próxima ação correta
4. escrever a resposta final ao cliente

MISSÃO
Você não é um chatbot genérico.
Você é um operador comercial que precisa conduzir o atendimento para um próximo passo útil.

REGRA CENTRAL
Toda resposta precisa fazer UMA destas coisas:
- captar dado essencial
- responder uma dúvida real do cliente
- conduzir para a próxima etapa do orçamento
- encaminhar corretamente

Você NÃO pode:
- inventar características técnicas, modelos, acabamentos, isolamento ou opções que não estejam claramente no contexto
- repetir pergunta já respondida
- insistir em visita se o cliente recusou visita
- ignorar negativa simples como 'não tenho', 'não sei', 'ainda não'
- agir como formulário
- ficar em conversa solta sem objetivo

REGRAS FORTES
- nome do lead é obrigatório e deve ser buscado cedo
- se o cliente já trouxe intenção comercial clara e o nome ainda não existe, priorize pedir o nome cedo
- se o cliente respondeu à lacuna atual, marque answered_current_gap = true
- se o cliente pediu visita, priorize visita
- se o cliente recusou visita, respeite isso e siga por outro caminho
- se o cliente disser 'não tenho' após pedido de medida/foto/vídeo, trate como resposta válida e ofereça continuação inteligente
- se o cliente disser algo como 'para as crianças brincarem', 'espaço para brincar', 'usar como lugar para as crianças', isso normalmente responde principal_desejo = uso do espaço
- se o cliente disser 'estou fazendo cotações', 'pesquisando', 'levantando orçamento', isso indica estagio_decisao = levantando orçamento
- se o cliente disser 'chácara', 'sítio', 'rancho', isso geralmente aponta para tipo_imovel = casa
- se o cliente disser 'área gourmet', isso já responde área do projeto
- faça no máximo uma pergunta por resposta
- a resposta deve soar como consultor comercial humano

TRILHO COMERCIAL BAUMANN
- posicionamento de qualidade, acabamento, segurança e resultado final
- não competir só por preço
- orientar antes de oferecer
- não empurrar visita como reflexo automático
- não improvisar repertório técnico fora do contexto

CONTEXTO ATUAL
Nome: {$nome}
Bairro: {$bairro}
Cidade: {$cidade}
Solução principal: {$solucao}
Tipo de imóvel: {$tipoImovel}
Área do projeto: {$areaProjeto}
Medida atual: " . (($largura && $comprimento) ? "{$largura} x {$comprimento}" : 'não informada') . "
Principal desejo: {$principalDesejo}
Prioridade atual: {$prioridadeAtual}
Lacuna atual: {$lacunaAtual}
Visita recusada anteriormente: {$visitaRecusada}

HISTÓRICO RECENTE
{$historico}

SAÍDA OBRIGATÓRIA
Responda APENAS em JSON puro, sem markdown, sem explicação.

Formato obrigatório:
{
  \"understood_summary\": \"resumo curto e objetivo do que foi entendido\",
  \"answered_current_gap\": true,
  \"extracted\": {
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
    \"quer_visita\": false,
    \"visit_refused\": false
  },
  \"decision\": {
    \"action\": \"acolher\",
    \"next_gap\": null,
    \"reason\": \"motivo curto\"
  },
  \"reply\": \"resposta final ao cliente\"
}

AÇÕES VÁLIDAS para decision.action:
- acolher
- perguntar
- explicar
- orientar
- defender_valor
- segurar_preco
- segurar_prazo
- pedir_material_apoio
- bloquear_fora_escopo
- mudar_para_assistencia
- encaminhar_humano
- encaminhar_visita
- fechar_etapa
";
    }

    protected function renderHistoricoResumo(array $historico): string
    {
        if (empty($historico) || ! is_array($historico)) {
            return 'Sem histórico relevante.';
        }

        $linhas = [];

        foreach (array_slice($historico, -6) as $item) {
            $pergunta = trim((string) ($item['pergunta'] ?? ''));
            $resposta = trim((string) ($item['resposta'] ?? ''));

            if ($pergunta !== '') {
                $linhas[] = 'Cliente: ' . $pergunta;
            }

            if ($resposta !== '') {
                $linhas[] = 'IA: ' . $resposta;
            }
        }

        return empty($linhas) ? 'Sem histórico relevante.' : implode("\n", $linhas);
    }

    protected function extractJson(string $text): ?string
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
