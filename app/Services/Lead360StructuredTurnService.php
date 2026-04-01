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
            return $this->emptyResult();
        }

        return $this->normalize($raw, $contexto);
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

        $lacunaAtual = $contexto['lacuna_atual'] ?? null;
        $gapRespondido = $normalized['decision']['next_gap'] ?? null;

        if (! $normalized['answered_current_gap'] && $lacunaAtual) {
            if ($this->gapWasAnsweredByExtraction($lacunaAtual, $normalized['extracted'])) {
                $normalized['answered_current_gap'] = true;
            }
        }

        if ($normalized['decision']['action'] === null) {
            $normalized['decision']['action'] = $this->inferActionFromResult($normalized, $contexto);
        }

        if ($normalized['decision']['next_gap'] === null && $gapRespondido) {
            $normalized['decision']['next_gap'] = $gapRespondido;
        }

        return $normalized;
    }

    protected function inferActionFromResult(array $normalized, array $contexto): string
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

        if (($contexto['lacuna_atual'] ?? null) === 'nome' && empty($e['nome'])) {
            return 'perguntar';
        }

        if ($normalized['answered_current_gap']) {
            return 'perguntar';
        }

        return 'acolher';
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
