<?php

namespace App\Services;

class Lead360StructuredTurnService
{
    public function __construct(
        protected OpenAIService $openAIService
    ) {
    }

    public function process(string $mensagem, array $contexto = []): array
    {
        $raw = $this->openAIService->processarTurnoEstruturado($mensagem, $contexto);

        if (! is_array($raw)) {
            $fallback = $this->emptyResult();
            $fallback['reply'] = $this->fallbackReply($contexto);
            return $fallback;
        }

        $normalized = $this->normalize($raw, $contexto);

        if (empty($normalized['reply'])) {
            $normalized['reply'] = $this->fallbackReply($contexto, $normalized);
        }

        return $normalized;
    }

    protected function normalize(array $raw, array $contexto): array
    {
        $extracted = $raw['extracted'] ?? [];
        $decision = $raw['decision'] ?? [];

        $normalized = [
            'understood_summary' => $this->cleanString($raw['understood_summary'] ?? null),
            'answered_current_gap' => (bool) ($raw['answered_current_gap'] ?? false),

            'extracted' => [
                'nome' => $this->cleanString($extracted['nome'] ?? null),
                'telefone' => $this->cleanString($extracted['telefone'] ?? null),
                'email' => $this->cleanString($extracted['email'] ?? null),
                'perfil_contato' => $this->normalizePerfilContato($extracted['perfil_contato'] ?? null),

                'cep' => $this->cleanString($extracted['cep'] ?? null),
                'endereco' => $this->cleanString($extracted['endereco'] ?? null),
                'bairro' => $this->cleanString($extracted['bairro'] ?? null),
                'cidade' => $this->cleanString($extracted['cidade'] ?? null),

                'solucao_principal' => $this->normalizeSolucaoPrincipal($extracted['solucao_principal'] ?? null),
                'solucao_subtipo' => $this->normalizeSolucaoSubtipo($extracted['solucao_subtipo'] ?? null),
                'fora_escopo' => (bool) ($extracted['fora_escopo'] ?? false),

                'tipo_imovel' => $this->normalizeTipoImovel($extracted['tipo_imovel'] ?? null),
                'area_projeto' => $this->normalizeAreaProjeto($extracted['area_projeto'] ?? null),
                'contexto_uso' => $this->normalizeContextoUso($extracted['contexto_uso'] ?? null),

                'largura' => $this->normalizeNumber($extracted['largura'] ?? null),
                'comprimento' => $this->normalizeNumber($extracted['comprimento'] ?? null),
                'area_informada_m2' => $this->normalizeNumber($extracted['area_informada_m2'] ?? null),

                'tem_foto' => (bool) ($extracted['tem_foto'] ?? false),
                'tem_video' => (bool) ($extracted['tem_video'] ?? false),
                'tem_projeto' => (bool) ($extracted['tem_projeto'] ?? false),

                'principal_desejo' => $this->normalizeContextoUso($extracted['principal_desejo'] ?? null),
                'prioridade_atual' => $this->normalizePrioridades($extracted['prioridade_atual'] ?? []),
                'urgencia' => $this->normalizeUrgencia($extracted['urgencia'] ?? null),
                'objecao_principal' => $this->normalizeObjecao($extracted['objecao_principal'] ?? null),
                'estagio_decisao' => $this->normalizeEstagioDecisao($extracted['estagio_decisao'] ?? null),

                'assistencia' => (bool) ($extracted['assistencia'] ?? false),
                'problema_relato' => $this->cleanString($extracted['problema_relato'] ?? null),
                'quer_visita' => (bool) ($extracted['quer_visita'] ?? false),
            ],

            'decision' => [
                'action' => $this->normalizeAction($decision['action'] ?? null),
                'reason' => $this->cleanString($decision['reason'] ?? null),
                'next_gap' => $this->normalizeGap($decision['next_gap'] ?? null),
            ],

            'reply' => $this->cleanString($raw['reply'] ?? null),
            'raw' => $raw,
        ];

        $normalized = $this->postProcess($normalized, $contexto);

        return $normalized;
    }

    protected function postProcess(array $normalized, array $contexto): array
    {
        $e = &$normalized['extracted'];
        $d = &$normalized['decision'];
        $lacunaAtual = $contexto['lacuna_atual'] ?? null;

        if (empty($e['principal_desejo']) && ! empty($e['contexto_uso'])) {
            $e['principal_desejo'] = $e['contexto_uso'];
        }

        if (! empty($e['quer_visita'])) {
            $d['action'] = 'encaminhar_visita';
            $d['next_gap'] = null;
            $normalized['answered_current_gap'] = true;
        }

        if ($lacunaAtual && ! $normalized['answered_current_gap']) {
            if ($this->gapWasAnsweredByExtraction($lacunaAtual, $e)) {
                $normalized['answered_current_gap'] = true;
            }
        }

        if ($d['action'] === null) {
            $d['action'] = $this->inferAction($normalized, $contexto);
        }

        if ($d['next_gap'] === null) {
            $d['next_gap'] = $this->inferNextGap($normalized, $contexto);
        }

        if (empty($normalized['reply'])) {
            $normalized['reply'] = $this->fallbackReply($contexto, $normalized);
        }

        return $normalized;
    }

    protected function inferAction(array $normalized, array $contexto): string
    {
        $e = $normalized['extracted'];

        if (! empty($e['fora_escopo'])) {
            return 'bloquear_fora_escopo';
        }

        if (! empty($e['assistencia'])) {
            return 'mudar_para_assistencia';
        }

        if (! empty($e['quer_visita'])) {
            return 'encaminhar_visita';
        }

        if (! empty($e['objecao_principal'])) {
            return 'defender_valor';
        }

        if (($contexto['lacuna_atual'] ?? null) === 'nome' && empty($e['nome'])) {
            return 'perguntar';
        }

        if ($normalized['answered_current_gap']) {
            return 'perguntar';
        }

        return 'acolher';
    }

    protected function inferNextGap(array $normalized, array $contexto): ?string
    {
        $e = $normalized['extracted'];
        $merged = $this->mergeContexto($contexto, $e);

        if (empty($merged['nome'])) {
            return 'nome';
        }

        if (empty($merged['bairro']) && empty($merged['cidade']) && empty($merged['cep'])) {
            return 'localizacao';
        }

        if (empty($merged['solucao_principal'])) {
            return 'solucao_principal';
        }

        if (empty($merged['area_projeto'])) {
            return 'area_projeto';
        }

        if (
            (empty($merged['largura']) || empty($merged['comprimento']))
            && empty($merged['tem_foto'])
            && empty($merged['tem_video'])
            && empty($merged['tem_projeto'])
        ) {
            return 'medida_ou_midia';
        }

        if (empty($merged['principal_desejo'])) {
            return 'principal_desejo';
        }

        if (empty($merged['prioridade_atual'])) {
            return 'prioridade_atual';
        }

        return null;
    }

    protected function fallbackReply(array $contexto, array $normalized = []): string
    {
        $e = $normalized['extracted'] ?? [];
        $action = $normalized['decision']['action'] ?? 'acolher';
        $nextGap = $normalized['decision']['next_gap'] ?? null;

        if (! empty($e['fora_escopo'])) {
            return 'Hoje nosso atendimento está focado nas soluções da linha Baumann, como coberturas em vidro, policarbonato e envidraçamentos. Se o seu projeto estiver nessa linha, eu sigo com você por aqui.';
        }

        if (! empty($e['assistencia'])) {
            return 'Entendi. Vamos tratar isso como assistência. Me confirma por favor o problema exato e, se puder, envie foto ou vídeo para eu deixar o atendimento bem direcionado.';
        }

        if (! empty($e['quer_visita'])) {
            return 'Perfeito. Nesse caso, faz sentido mesmo seguir com uma visita para avaliar melhor o local. Me diz seu nome para eu registrar seu atendimento certinho e deixar isso encaminhado da forma correta.';
        }

        return match ($nextGap) {
            'nome' => 'Perfeito. Antes de seguir, me diz seu nome para eu registrar seu atendimento certinho.',
            'localizacao' => 'Ótimo. Pra eu te orientar com mais precisão, me passa o CEP do local da instalação ou pelo menos o bairro e a cidade.',
            'solucao_principal' => 'Me conta uma coisa pra eu te orientar certo: você está buscando cobertura, fechamento, sacada ou outra solução da nossa linha?',
            'area_projeto' => 'Entendi. E essa instalação é para qual área exatamente? Garagem, quintal, corredor, espaço gourmet, fundos, varanda...?',
            'medida_ou_midia' => 'Perfeito. Se você já tiver a medida aproximada ou alguma foto, vídeo ou projeto do local, isso já ajuda bastante a te orientar certo.',
            'principal_desejo' => 'Agora me ajuda com uma parte importante: o que você mais busca com esse projeto? Proteção, conforto, estética, segurança ou uso do espaço?',
            'prioridade_atual' => 'Entendi. E olhando para esse projeto agora, o que pesa mais na sua decisão: prazo, qualidade, preço, forma de pagamento ou outro ponto?',
            default => 'Oi! Tudo bem 🙂 Sou o assistente da Baumann e vou te ajudar por aqui. Me conta: você está buscando cobertura, fechamento, sacada ou outra solução da nossa linha?',
        };
    }

    protected function gapWasAnsweredByExtraction(string $gap, array $e): bool
    {
        return match ($gap) {
            'nome' => ! empty($e['nome']),
            'localizacao' => ! empty($e['cep']) || ! empty($e['bairro']) || ! empty($e['cidade']),
            'solucao_principal' => ! empty($e['solucao_principal']),
            'tipo_imovel' => ! empty($e['tipo_imovel']),
            'area_projeto' => ! empty($e['area_projeto']),
            'medida_ou_midia' => (! empty($e['largura']) && ! empty($e['comprimento'])) || ! empty($e['tem_foto']) || ! empty($e['tem_video']) || ! empty($e['tem_projeto']),
            'principal_desejo' => ! empty($e['principal_desejo']) || ! empty($e['contexto_uso']),
            'prioridade_atual' => ! empty($e['prioridade_atual']),
            default => false,
        };
    }

    protected function mergeContexto(array $contexto, array $e): array
    {
        $merged = $contexto;

        foreach ($e as $chave => $valor) {
            if ($valor === null) {
                continue;
            }

            if (is_array($valor) && empty($valor)) {
                continue;
            }

            if (is_bool($valor)) {
                if ($valor === true) {
                    $merged[$chave] = true;
                }
                continue;
            }

            $merged[$chave] = $valor;
        }

        return $merged;
    }

    protected function emptyResult(): array
    {
        return [
            'understood_summary' => null,
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
                'fora_escopo' => false,
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
            ],
            'decision' => [
                'action' => 'acolher',
                'reason' => null,
                'next_gap' => null,
            ],
            'reply' => null,
            'raw' => [],
        ];
    }

    protected function cleanString($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    protected function normalizeNumber($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = str_replace(',', '.', trim((string) $value));

        return is_numeric($value) ? $value : null;
    }

    protected function normalizePerfilContato($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'arquiteto', 'arquiteta' => 'arquiteto',
            'engenheiro', 'engenheira' => 'engenheiro',
            'sindico', 'sindica' => 'sindico',
            'comercial', 'empresa', 'pj' => 'comercial',
            default => null,
        };
    }

    protected function normalizeSolucaoPrincipal($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'cobertura' => 'cobertura',
            'fechamento' => 'fechamento',
            'sacada' => 'sacada',
            'box' => 'box',
            default => null,
        };
    }

    protected function normalizeSolucaoSubtipo($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'retratil' => 'retratil',
            'fixa', 'fixo' => 'fixa',
            default => null,
        };
    }

    protected function normalizeTipoImovel($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'casa', 'chacara', 'sitio', 'rancho', 'casa_de_campo', 'casa_rural' => 'casa',
            'apartamento' => 'apartamento',
            'comercial', 'empresa', 'loja', 'galpao', 'faculdade', 'estudio', 'recepcao' => 'comercial',
            default => null,
        };
    }

    protected function normalizeAreaProjeto($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'garagem' => 'garagem',
            'quintal' => 'quintal',
            'corredor' => 'corredor',
            'espaco_gourmet', 'gourmet', 'area_gourmet' => 'espaco gourmet',
            'fundos', 'area_dos_fundos' => 'fundos',
            'sacada' => 'sacada',
            'varanda' => 'varanda',
            'varanda_gourmet' => 'varanda gourmet',
            'piscina' => 'piscina',
            'area_externa' => 'area externa',
            'estudio', 'estudio_fotografico' => 'estudio fotografico',
            'frente' => 'frente',
            'lateral' => 'lateral',
            'terraco' => 'terraco',
            'lavanderia' => 'lavanderia',
            'jardim' => 'jardim',
            'recepcao' => 'recepcao',
            default => null,
        };
    }

    protected function normalizeContextoUso($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'protecao_chuva', 'chuva' => 'protecao chuva',
            'conforto_termico', 'calor' => 'conforto termico',
            'estetica' => 'estetica',
            'uso_do_espaco', 'espaco_para_criancas', 'criancas_brincarem', 'brincar', 'uso_familiar' => 'uso do espaco',
            'seguranca' => 'seguranca',
            default => $this->cleanString($value ? str_replace('_', ' ', $value) : null),
        };
    }

    protected function normalizePrioridades($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $out = [];

        foreach ($value as $item) {
            $item = $this->slug($item);

            $normalized = match ($item) {
                'prazo' => 'prazo',
                'qualidade' => 'qualidade',
                'preco' => 'preco',
                'pagamento', 'parcelamento' => 'pagamento',
                default => null,
            };

            if ($normalized) {
                $out[] = $normalized;
            }
        }

        return array_values(array_unique($out));
    }

    protected function normalizeUrgencia($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'alta', 'urgente' => 'alta',
            'media' => 'media',
            'baixa' => 'baixa',
            default => null,
        };
    }

    protected function normalizeObjecao($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'preco' => 'preco',
            'prazo' => 'prazo',
            default => null,
        };
    }

    protected function normalizeEstagioDecisao($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'levantando_orcamento', 'pesquisa', 'pesquisando', 'cotacao', 'cotacoes' => 'levantando orçamento',
            'fechamento', 'pronto_para_fechar' => 'fechamento',
            default => null,
        };
    }

    protected function normalizeAction($value): ?string
    {
        $value = $this->slug($value);

        $permitidas = [
            'acolher',
            'perguntar',
            'explicar',
            'orientar',
            'defender_valor',
            'segurar_preco',
            'segurar_prazo',
            'pedir_material_apoio',
            'bloquear_fora_escopo',
            'mudar_para_assistencia',
            'encaminhar_humano',
            'encaminhar_visita',
            'fechar_etapa',
        ];

        return in_array($value, $permitidas, true) ? $value : null;
    }

    protected function normalizeGap($value): ?string
    {
        $value = $this->slug($value);

        $permitidas = [
            'nome',
            'localizacao',
            'solucao_principal',
            'tipo_imovel',
            'area_projeto',
            'medida_ou_midia',
            'principal_desejo',
            'prioridade_atual',
            'handoff',
        ];

        return in_array($value, $permitidas, true) ? $value : null;
    }

    protected function slug($value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '_', $value);
        $value = trim($value, '_');

        return $value === '' ? null : $value;
    }
}
