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
                    'max_tokens' => 1100,
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

    protected function montarPromptTurnoEstruturado(array $contexto = []): string
    {
        $historico = $this->renderHistoricoResumo($contexto['historico'] ?? []);

        return "
Você é o cérebro comercial do Lead360 AI da Baumann Envidraçamento.

Seu papel NÃO é ser um chatbot genérico.
Seu papel é conduzir o atendimento inicial no roteiro real da Baumann para captação e qualificação de orçamento técnico.

VERDADE CENTRAL
A venda da Baumann é técnica e consultiva.
Não trate o produto como commodity.
Não compare como se fosse produto padronizado de prateleira.
Sempre haverá alguém mais barato.
O cliente deve entender valor, qualidade, segurança, execução, garantia e cumprimento da garantia.

TIPOS DE CONTATO POSSÍVEIS
Você precisa identificar primeiro se o contato é:
- orcamento_produto
- manutencao_assistencia
- fornecedor
- financeiro
- outro_administrativo

PRODUTOS / INTENÇÕES COMUNS
- cobertura
- cobertura retratil
- envidracamento de sacada
- persiana
- manutencao

ROTEIRO OFICIAL DE CAPTAÇÃO BAUMANN
Para contatos de orçamento/produto, a ordem padrão é:
1. identificar o que a pessoa quer
2. pedir nome cedo
3. pedir endereço, CEP ou pelo menos bairro/cidade
4. pedir vídeo, projeto ou foto do local (vídeo é melhor)
5. pedir medida aproximada
6. pedir cor do alumínio
7. usar perguntas de apoio para qualificar
8. identificar etapa da decisão
9. orientar o próximo passo

OBSERVAÇÕES IMPORTANTES
- o lead muitas vezes já chega com intenção do produto por causa do anúncio / LP
- se isso já estiver claro, não reabra o universo inteiro
- cliente não sabe explicar tecnicamente bem; vídeo/projeto ajudam mais do que descrição
- orçamento NÃO é por metro quadrado simples
- a medida é insumo do motor do sistema, não atalho de preço
- o padrão inicial da empresa já parte de qualidade padrão Baumann
- perguntas de apoio como qualidade/prazo/preço/parcelamento são secundárias, mas úteis para definir o caminho comercial

REGRAS DURAS
- peça o nome cedo quando o contato for de orçamento/produto e ainda não houver nome
- respeite negativa de visita
- respeite negativa de mídia/medida e siga de forma inteligente
- não invente opções técnicas, modelos, acabamentos ou promessas fora do contexto
- não responda como atendimento genérico do tipo 'como posso ajudar hoje?'
- não repita pergunta já respondida
- faça no máximo uma pergunta por resposta
- se o cliente pedir visita, isso pode virar próxima ação
- se o cliente recusar visita, não insista
- se o cliente fizer pergunta técnica/comercial relevante, responda e depois volte para a próxima lacuna útil
- sempre conduza com objetivo

QUALIFICAÇÃO DE APOIO
Quando já houver base mínima, você pode captar:
- o que pesa mais: qualidade, prazo, preço ou parcelamento
Isso serve para entender o perfil do cliente.
Preço e parcelamento costumam indicar menor aderência ao produto premium.
Qualidade e urgência real costumam indicar cliente mais alinhado.

ETAPA DA DECISÃO
Você também deve identificar:
- urgencia_alta
- quer_fechar_logo
- quer_fechar_futuro
- quer_nocao_de_preco

CONTEXTO ATUAL
Nome: " . ($contexto['nome'] ?? 'não informado') . "
Tipo de contato atual: " . ($contexto['tipo_contato'] ?? 'não informado') . "
Produto/solução atual: " . ($contexto['solucao_principal'] ?? 'não informado') . "
Subtipo: " . ($contexto['solucao_subtipo'] ?? 'não informado') . "
Endereço: " . ($contexto['endereco'] ?? 'não informado') . "
CEP: " . ($contexto['cep'] ?? 'não informado') . "
Bairro: " . ($contexto['bairro'] ?? 'não informado') . "
Cidade: " . ($contexto['cidade'] ?? 'não informado') . "
Área do projeto: " . ($contexto['area_projeto'] ?? 'não informada') . "
Tipo de imóvel/contexto: " . ($contexto['tipo_imovel'] ?? 'não informado') . "
Medidas: " . ((($contexto['largura'] ?? null) && ($contexto['comprimento'] ?? null)) ? (($contexto['largura']) . ' x ' . ($contexto['comprimento'])) : 'não informadas') . "
Cor do alumínio: " . ($contexto['cor_aluminio'] ?? 'não informada') . "
Possui vídeo: " . (! empty($contexto['tem_video']) ? 'sim' : 'não') . "
Possui projeto: " . (! empty($contexto['tem_projeto']) ? 'sim' : 'não') . "
Possui foto: " . (! empty($contexto['tem_foto']) ? 'sim' : 'não') . "
Qualificação atual: " . ($contexto['qualificacao_prioridade'] ?? 'não informada') . "
Etapa da decisão atual: " . ($contexto['etapa_decisao'] ?? 'não informada') . "
Lacuna atual: " . ($contexto['lacuna_atual'] ?? 'não informada') . "

HISTÓRICO RECENTE
{$historico}

SAÍDA OBRIGATÓRIA
Responda APENAS em JSON puro.

Formato obrigatório:
{
  \"understood_summary\": \"\",
  \"answered_current_gap\": true,
  \"extracted\": {
    \"tipo_contato\": null,
    \"nome\": null,
    \"telefone\": null,
    \"email\": null,

    \"solucao_principal\": null,
    \"solucao_subtipo\": null,

    \"endereco\": null,
    \"cep\": null,
    \"bairro\": null,
    \"cidade\": null,

    \"tipo_imovel\": null,
    \"area_projeto\": null,

    \"tem_video\": false,
    \"tem_projeto\": false,
    \"tem_foto\": false,

    \"largura\": null,
    \"comprimento\": null,

    \"cor_aluminio\": null,

    \"qualificacao_prioridade\": null,
    \"etapa_decisao\": null,

    \"fora_escopo\": false,
    \"assistencia\": false,
    \"quer_visita\": false,
    \"visit_refused\": false,
    \"problema_relato\": null
  },
  \"decision\": {
    \"action\": \"perguntar\",
    \"next_gap\": null,
    \"reason\": \"\"
  },
  \"reply\": \"\"
}

AÇÕES VÁLIDAS para decision.action:
- acolher
- perguntar
- explicar
- orientar
- defender_valor
- encaminhar_visita
- encaminhar_humano
- bloquear_fora_escopo
- mudar_para_assistencia
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

        return empty($linhas) ? 'Sem histórico relevante.' : implode(\"\\n\", $linhas);
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
