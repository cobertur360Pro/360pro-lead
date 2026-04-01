<?php

namespace App\Services;

class Lead360SemanticExtractorService
{
    public function __construct(
        protected OpenAIService $openAIService
    ) {
    }

    public function extract(string $mensagem, array $contexto = []): array
    {
        $data = $this->openAIService->extrairSemanticaLead($mensagem, $contexto);

        if (! is_array($data)) {
            return $this->emptyResult();
        }

        return $this->normalizeResult($data, $contexto);
    }

    protected function normalizeResult(array $data, array $contexto): array
    {
        $tipoMensagem = $this->normalizeTipoMensagem($data['tipo_mensagem'] ?? null);
        $lacunaRespondida = $this->normalizeLacuna($data['lacuna_respondida'] ?? null);

        $resultado = [
            'tipo_mensagem' => $tipoMensagem,
            'responde_lacuna_atual' => (bool) ($data['responde_lacuna_atual'] ?? false),
            'trouxe_dado_novo' => (bool) ($data['trouxe_dado_novo'] ?? false),
            'mudou_assunto' => (bool) ($data['mudou_assunto'] ?? false),

            'nome' => $this->cleanString($data['nome'] ?? null),
            'telefone' => $this->cleanString($data['telefone'] ?? null),
            'email' => $this->cleanString($data['email'] ?? null),
            'perfil_contato' => $this->normalizePerfilContato($data['perfil_contato'] ?? null),

            'cep' => $this->cleanString($data['cep'] ?? null),
            'endereco' => $this->cleanString($data['endereco'] ?? null),
            'bairro' => $this->cleanString($data['bairro'] ?? null),
            'cidade' => $this->cleanString($data['cidade'] ?? null),

            'solucao_principal' => $this->normalizeSolucaoPrincipal($data['solucao_principal'] ?? null),
            'solucao_subtipo' => $this->normalizeSolucaoSubtipo($data['solucao_subtipo'] ?? null),
            'fora_escopo' => (bool) ($data['fora_escopo'] ?? false),

            'tipo_imovel' => $this->normalizeTipoImovel($data['tipo_imovel'] ?? null),
            'area_projeto' => $this->normalizeAreaProjeto($data['area_projeto'] ?? null),
            'contexto_uso' => $this->normalizeContextoUso($data['contexto_uso'] ?? null),

            'largura' => $this->normalizeNumber($data['largura'] ?? null),
            'comprimento' => $this->normalizeNumber($data['comprimento'] ?? null),
            'area_informada_m2' => $this->normalizeNumber($data['area_informada_m2'] ?? null),

            'tem_foto' => (bool) ($data['tem_foto'] ?? false),
            'tem_video' => (bool) ($data['tem_video'] ?? false),
            'tem_projeto' => (bool) ($data['tem_projeto'] ?? false),

            'principal_desejo' => $this->normalizePrincipalDesejo($data['principal_desejo'] ?? null),
            'prioridade_atual' => $this->normalizePrioridades($data['prioridade_atual'] ?? []),
            'urgencia' => $this->normalizeUrgencia($data['urgencia'] ?? null),
            'objecao_principal' => $this->normalizeObjecao($data['objecao_principal'] ?? null),
            'estagio_decisao' => $this->normalizeEstagioDecisao($data['estagio_decisao'] ?? null),

            'assistencia' => (bool) ($data['assistencia'] ?? false),
            'problema_relato' => $this->cleanString($data['problema_relato'] ?? null),

            'confianca_geral' => $this->normalizeConfianca($data['confianca_geral'] ?? null),
            'campos_baixa_confianca' => $this->normalizeStringArray($data['campos_baixa_confianca'] ?? []),

            'lacuna_respondida' => $lacunaRespondida,
        ];

        if (! $resultado['responde_lacuna_atual'] && ! empty($lacunaRespondida)) {
            $lacunaAtual = $contexto['lacuna_atual'] ?? null;
            if ($lacunaAtual && $lacunaAtual === $lacunaRespondida) {
                $resultado['responde_lacuna_atual'] = true;
            }
        }

        if (! $resultado['trouxe_dado_novo']) {
            $resultado['trouxe_dado_novo'] = $this->hasUsefulData($resultado);
        }

        return $resultado;
    }

    protected function hasUsefulData(array $resultado): bool
    {
        $campos = [
            'nome',
            'telefone',
            'email',
            'cep',
            'bairro',
            'cidade',
            'solucao_principal',
            'solucao_subtipo',
            'tipo_imovel',
            'area_projeto',
            'contexto_uso',
            'largura',
            'comprimento',
            'area_informada_m2',
            'principal_desejo',
            'urgencia',
            'objecao_principal',
            'estagio_decisao',
            'problema_relato',
        ];

        foreach ($campos as $campo) {
            if (! empty($resultado[$campo])) {
                return true;
            }
        }

        if (! empty($resultado['prioridade_atual'])) {
            return true;
        }

        if (! empty($resultado['tem_foto']) || ! empty($resultado['tem_video']) || ! empty($resultado['tem_projeto'])) {
            return true;
        }

        return false;
    }

    protected function emptyResult(): array
    {
        return [
            'tipo_mensagem' => null,
            'responde_lacuna_atual' => false,
            'trouxe_dado_novo' => false,
            'mudou_assunto' => false,

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

            'confianca_geral' => 'baixa',
            'campos_baixa_confianca' => [],
            'lacuna_respondida' => null,
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

    protected function normalizeStringArray($value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($item) {
            return is_string($item) ? trim($item) : null;
        }, $value)));
    }

    protected function normalizeTipoMensagem(?string $value): ?string
    {
        $value = $this->slug($value);

        $permitidos = [
            'saudacao',
            'abertura_comercial',
            'resposta_curta',
            'resposta_objetiva',
            'envio_dado',
            'objecao',
            'urgencia',
            'pedido_preco',
            'pedido_prazo',
            'pedido_visita',
            'assistencia',
            'fora_escopo',
            'formalizacao',
            'pesquisa',
        ];

        return in_array($value, $permitidos, true) ? $value : null;
    }

    protected function normalizeLacuna(?string $value): ?string
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
        ];

        return in_array($value, $permitidas, true) ? $value : null;
    }

    protected function normalizePerfilContato(?string $value): ?string
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

    protected function normalizeSolucaoPrincipal(?string $value): ?string
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

    protected function normalizeSolucaoSubtipo(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'retratil', 'retratil_' => 'retratil',
            'fixa', 'fixo' => 'fixa',
            default => null,
        };
    }

    protected function normalizeTipoImovel(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'casa', 'chacara', 'sitio', 'rancho', 'casa_de_campo', 'casa_rural' => 'casa',
            'apartamento' => 'apartamento',
            'comercial', 'empresa', 'loja', 'galpao', 'faculdade', 'estudio', 'recepcao' => 'comercial',
            default => null,
        };
    }

    protected function normalizeAreaProjeto(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'garagem' => 'garagem',
            'quintal' => 'quintal',
            'corredor' => 'corredor',
            'espaco_gourmet', 'gourmet' => 'espaco gourmet',
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

    protected function normalizeContextoUso(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'protecao_chuva', 'chuva' => 'protecao chuva',
            'conforto_termico', 'calor' => 'conforto termico',
            'estetica' => 'estetica',
            'uso_do_espaco', 'brincar', 'criancas_brincarem', 'espaco_para_criancas' => 'uso do espaco',
            'seguranca' => 'seguranca',
            default => $this->cleanString($value ? str_replace('_', ' ', $value) : null),
        };
    }

    protected function normalizePrincipalDesejo(?string $value): ?string
    {
        return $this->normalizeContextoUso($value);
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

    protected function normalizeUrgencia(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'alta', 'urgente' => 'alta',
            'media' => 'media',
            'baixa' => 'baixa',
            default => null,
        };
    }

    protected function normalizeObjecao(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'preco', 'preço' => 'preco',
            'prazo' => 'prazo',
            default => null,
        };
    }

    protected function normalizeEstagioDecisao(?string $value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'levantando_orcamento', 'levantando_orçamento', 'pesquisa', 'pesquisando' => 'levantando orçamento',
            'fechamento', 'pronto_para_fechar' => 'fechamento',
            default => null,
        };
    }

    protected function normalizeConfianca(?string $value): string
    {
        $value = $this->slug($value);

        return match ($value) {
            'alta' => 'alta',
            'media' => 'media',
            default => 'baixa',
        };
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
