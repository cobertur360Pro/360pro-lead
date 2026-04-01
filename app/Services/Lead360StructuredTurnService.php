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

        $normalized = $this->normalize($raw);
        $normalized = $this->postProcess($normalized, $contexto);

        if (empty($normalized['reply'])) {
            $normalized['reply'] = $this->fallbackReply($contexto, $normalized);
        }

        return $normalized;
    }

    protected function normalize(array $raw): array
    {
        $extracted = $raw['extracted'] ?? [];
        $decision = $raw['decision'] ?? [];

        return [
            'understood_summary' => $this->cleanString($raw['understood_summary'] ?? null),
            'answered_current_gap' => (bool) ($raw['answered_current_gap'] ?? false),
            'extracted' => [
                'tipo_contato' => $this->normalizeTipoContato($extracted['tipo_contato'] ?? null),
                'nome' => $this->cleanString($extracted['nome'] ?? null),
                'telefone' => $this->cleanString($extracted['telefone'] ?? null),
                'email' => $this->cleanString($extracted['email'] ?? null),

                'solucao_principal' => $this->normalizeSolucao($extracted['solucao_principal'] ?? null),
                'solucao_subtipo' => $this->normalizeSubtipo($extracted['solucao_subtipo'] ?? null),

                'endereco' => $this->cleanString($extracted['endereco'] ?? null),
                'cep' => $this->cleanString($extracted['cep'] ?? null),
                'bairro' => $this->cleanString($extracted['bairro'] ?? null),
                'cidade' => $this->cleanString($extracted['cidade'] ?? null),

                'tipo_imovel' => $this->normalizeTipoImovel($extracted['tipo_imovel'] ?? null),
                'area_projeto' => $this->normalizeArea($extracted['area_projeto'] ?? null),

                'tem_video' => (bool) ($extracted['tem_video'] ?? false),
                'tem_projeto' => (bool) ($extracted['tem_projeto'] ?? false),
                'tem_foto' => (bool) ($extracted['tem_foto'] ?? false),

                'largura' => $this->normalizeNumber($extracted['largura'] ?? null),
                'comprimento' => $this->normalizeNumber($extracted['comprimento'] ?? null),

                'cor_aluminio' => $this->normalizeCorAluminio($extracted['cor_aluminio'] ?? null),

                'qualificacao_prioridade' => $this->normalizeQualificacao($extracted['qualificacao_prioridade'] ?? null),
                'etapa_decisao' => $this->normalizeEtapaDecisao($extracted['etapa_decisao'] ?? null),

                'fora_escopo' => (bool) ($extracted['fora_escopo'] ?? false),
                'assistencia' => (bool) ($extracted['assistencia'] ?? false),
                'quer_visita' => (bool) ($extracted['quer_visita'] ?? false),
                'visit_refused' => (bool) ($extracted['visit_refused'] ?? false),
                'problema_relato' => $this->cleanString($extracted['problema_relato'] ?? null),
            ],
            'decision' => [
                'action' => $this->normalizeAction($decision['action'] ?? null),
                'next_gap' => $this->normalizeGap($decision['next_gap'] ?? null),
                'reason' => $this->cleanString($decision['reason'] ?? null),
            ],
            'reply' => $this->cleanString($raw['reply'] ?? null),
            'raw' => $raw,
        ];
    }

    protected function postProcess(array $turno, array $contexto): array
    {
        $e = &$turno['extracted'];
        $d = &$turno['decision'];

        $tipoContatoAtual = $contexto['tipo_contato'] ?? null;
        if (empty($e['tipo_contato']) && $tipoContatoAtual) {
            $e['tipo_contato'] = $tipoContatoAtual;
        }

        if (empty($e['tipo_contato'])) {
            $e['tipo_contato'] = $this->inferTipoContato($e, $contexto);
        }

        if (! empty($e['visit_refused'])) {
            $contexto['visita_recusada'] = true;
        }

        if (! empty($e['quer_visita']) && empty($contexto['visita_recusada'])) {
            $d['action'] = 'encaminhar_visita';
            $d['next_gap'] = empty($contexto['nome']) && empty($e['nome']) ? 'nome' : null;
            $turno['answered_current_gap'] = true;
        }

        if (! empty($e['assistencia'])) {
            $d['action'] = 'mudar_para_assistencia';
            $d['next_gap'] = null;
        }

        if (! empty($e['fora_escopo'])) {
            $d['action'] = 'bloquear_fora_escopo';
            $d['next_gap'] = null;
        }

        if (empty($d['next_gap'])) {
            $d['next_gap'] = $this->inferNextGap($contexto, $e);
        }

        if (empty($d['action'])) {
            $d['action'] = $this->inferAction($contexto, $e, $d['next_gap']);
        }

        if (($contexto['lacuna_atual'] ?? null) && ! $turno['answered_current_gap']) {
            $turno['answered_current_gap'] = $this->answeredGap(
                $contexto['lacuna_atual'],
                $e
            );
        }

        if (empty($turno['reply'])) {
            $turno['reply'] = $this->fallbackReply($contexto, $turno);
        }

        return $turno;
    }

    protected function inferTipoContato(array $e, array $contexto): string
    {
        if (! empty($e['assistencia'])) {
            return 'manutencao_assistencia';
        }

        if (! empty($e['solucao_principal'])) {
            return 'orcamento_produto';
        }

        return $contexto['tipo_contato'] ?? 'orcamento_produto';
    }

    protected function inferNextGap(array $contexto, array $e): ?string
    {
        $tipoContato = $e['tipo_contato'] ?? ($contexto['tipo_contato'] ?? 'orcamento_produto');

        if ($tipoContato !== 'orcamento_produto') {
            return null;
        }

        $merged = $this->merge($contexto, $e);

        if (empty($merged['nome'])) {
            return 'nome';
        }

        if (empty($merged['endereco']) && empty($merged['cep']) && empty($merged['bairro']) && empty($merged['cidade'])) {
            return 'endereco';
        }

        if (empty($merged['tem_video']) && empty($merged['tem_projeto']) && empty($merged['tem_foto'])) {
            return 'visual';
        }

        if (empty($merged['largura']) || empty($merged['comprimento'])) {
            return 'medida';
        }

        if (empty($merged['cor_aluminio'])) {
            return 'cor_aluminio';
        }

        if (empty($merged['qualificacao_prioridade'])) {
            return 'qualificacao';
        }

        if (empty($merged['etapa_decisao'])) {
            return 'etapa_decisao';
        }

        return null;
    }

    protected function inferAction(array $contexto, array $e, ?string $nextGap): string
    {
        if (! empty($e['fora_escopo'])) {
            return 'bloquear_fora_escopo';
        }

        if (! empty($e['assistencia'])) {
            return 'mudar_para_assistencia';
        }

        if (! empty($e['quer_visita']) && empty($contexto['visita_recusada'])) {
            return 'encaminhar_visita';
        }

        if ($nextGap === null) {
            return 'fechar_etapa';
        }

        return 'perguntar';
    }

    protected function answeredGap(string $gap, array $e): bool
    {
        return match ($gap) {
            'nome' => ! empty($e['nome']),
            'endereco' => ! empty($e['endereco']) || ! empty($e['cep']) || ! empty($e['bairro']) || ! empty($e['cidade']),
            'visual' => ! empty($e['tem_video']) || ! empty($e['tem_projeto']) || ! empty($e['tem_foto']),
            'medida' => ! empty($e['largura']) && ! empty($e['comprimento']),
            'cor_aluminio' => ! empty($e['cor_aluminio']),
            'qualificacao' => ! empty($e['qualificacao_prioridade']),
            'etapa_decisao' => ! empty($e['etapa_decisao']),
            default => false,
        };
    }

    protected function fallbackReply(array $contexto, array $turno = []): string
    {
        $e = $turno['extracted'] ?? [];
        $d = $turno['decision'] ?? [];
        $nextGap = $d['next_gap'] ?? null;
        $tipoContato = $e['tipo_contato'] ?? ($contexto['tipo_contato'] ?? 'orcamento_produto');

        if (! empty($e['fora_escopo'])) {
            return 'Hoje nosso atendimento está focado nas soluções da linha Baumann. Se o seu assunto estiver dentro dessa linha, eu sigo com você por aqui.';
        }

        if (! empty($e['assistencia'])) {
            return 'Entendi. Vamos tratar isso como assistência. Me confirma por favor o problema e, se puder, envie vídeo ou foto para eu direcionar isso corretamente.';
        }

        if (! empty($e['quer_visita']) && empty($contexto['visita_recusada'])) {
            if (empty($contexto['nome']) && empty($e['nome'])) {
                return 'Perfeito. Faz sentido sim avançarmos por visita nesse caso. Antes de encaminhar isso da forma correta, me diz seu nome para eu registrar seu atendimento certinho.';
            }

            return 'Perfeito. Faz sentido sim avançarmos por visita nesse caso. Vou deixar isso encaminhado para verificarmos a melhor disponibilidade com a equipe responsável.';
        }

        if ($tipoContato !== 'orcamento_produto') {
            return 'Entendi. Me diz seu nome e me explica rapidinho o assunto para eu te direcionar da forma correta.';
        }

        return match ($nextGap) {
            'nome' => 'Perfeito. Antes de seguir com seu atendimento, me diz seu nome para eu registrar tudo certinho.',
            'endereco' => 'Ótimo. Agora me passa o endereço da instalação. Se preferir, pode ser o CEP ou pelo menos o bairro e a cidade.',
            'visual' => 'Perfeito. Agora, se você tiver vídeo do local, melhor ainda. Vídeo ajuda bastante porque mostra direitinho o vão e a instalação. Se não tiver, pode ser projeto ou foto.',
            'medida' => 'Perfeito. Você tem a medida aproximada? Pode ser algo como 6x4, mesmo que seja uma noção inicial.',
            'cor_aluminio' => 'Perfeito. E qual cor de alumínio você está pensando?',
            'qualificacao' => 'Pra eu entender melhor o caminho ideal para você, o que pesa mais hoje: qualidade, prazo, preço ou parcelamento?',
            'etapa_decisao' => 'Entendi. E hoje você está com muita urgência, quer fechar logo, pretende fechar no futuro ou quer primeiro ter uma noção de valor?',
            default => 'Perfeito. Com o que você me passou, já consigo organizar bem seu atendimento. Vou seguir para o próximo passo da forma mais precisa.',
        };
    }

    protected function emptyResult(): array
    {
        return [
            'understood_summary' => null,
            'answered_current_gap' => false,
            'extracted' => [
                'tipo_contato' => null,
                'nome' => null,
                'telefone' => null,
                'email' => null,
                'solucao_principal' => null,
                'solucao_subtipo' => null,
                'endereco' => null,
                'cep' => null,
                'bairro' => null,
                'cidade' => null,
                'tipo_imovel' => null,
                'area_projeto' => null,
                'tem_video' => false,
                'tem_projeto' => false,
                'tem_foto' => false,
                'largura' => null,
                'comprimento' => null,
                'cor_aluminio' => null,
                'qualificacao_prioridade' => null,
                'etapa_decisao' => null,
                'fora_escopo' => false,
                'assistencia' => false,
                'quer_visita' => false,
                'visit_refused' => false,
                'problema_relato' => null,
            ],
            'decision' => [
                'action' => 'perguntar',
                'next_gap' => 'nome',
                'reason' => null,
            ],
            'reply' => null,
            'raw' => [],
        ];
    }

    protected function merge(array $contexto, array $extraido): array
    {
        $merged = $contexto;

        foreach ($extraido as $k => $v) {
            if ($v === null) {
                continue;
            }

            if (is_bool($v)) {
                if ($v === true) {
                    $merged[$k] = true;
                }
                continue;
            }

            if (is_array($v) && empty($v)) {
                continue;
            }

            $merged[$k] = $v;
        }

        return $merged;
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

    protected function normalizeTipoContato($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'orcamento_produto', 'orcamento' => 'orcamento_produto',
            'manutencao_assistencia', 'assistencia', 'manutencao' => 'manutencao_assistencia',
            'fornecedor' => 'fornecedor',
            'financeiro' => 'financeiro',
            'outro_administrativo' => 'outro_administrativo',
            default => null,
        };
    }

    protected function normalizeSolucao($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'cobertura' => 'cobertura',
            'envidracamento_de_sacada', 'sacada', 'envidracamento_sacada' => 'envidracamento de sacada',
            'persiana' => 'persiana',
            'manutencao' => 'manutencao',
            default => null,
        };
    }

    protected function normalizeSubtipo($value): ?string
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
            'casa', 'chacara', 'sitio', 'rancho', 'casa_rural' => 'casa',
            'apartamento' => 'apartamento',
            'hotel', 'comercial', 'empresa', 'loja', 'condominio', 'portaria' => 'comercial',
            default => null,
        };
    }

    protected function normalizeArea($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'area_gourmet', 'espaco_gourmet', 'gourmet' => 'area gourmet',
            'garagem' => 'garagem',
            'sacada' => 'sacada',
            'portaria' => 'portaria',
            'quintal' => 'quintal',
            'varanda' => 'varanda',
            default => $this->cleanString($value ? str_replace('_', ' ', $value) : null),
        };
    }

    protected function normalizeCorAluminio($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'branco' => 'branco',
            'preto' => 'preto',
            'bronze' => 'bronze',
            'natural' => 'natural',
            default => $this->cleanString($value ? str_replace('_', ' ', $value) : null),
        };
    }

    protected function normalizeQualificacao($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'qualidade' => 'qualidade',
            'prazo' => 'prazo',
            'preco', 'preço' => 'preco',
            'parcelamento' => 'parcelamento',
            default => null,
        };
    }

    protected function normalizeEtapaDecisao($value): ?string
    {
        $value = $this->slug($value);

        return match ($value) {
            'urgencia_alta', 'urgencia' => 'urgencia_alta',
            'quer_fechar_logo' => 'quer_fechar_logo',
            'quer_fechar_futuro' => 'quer_fechar_futuro',
            'quer_nocao_de_preco', 'nocao_preco' => 'quer_nocao_de_preco',
            default => null,
        };
    }

    protected function normalizeAction($value): ?string
    {
        $value = $this->slug($value);

        $valid = [
            'acolher',
            'perguntar',
            'explicar',
            'orientar',
            'defender_valor',
            'encaminhar_visita',
            'encaminhar_humano',
            'bloquear_fora_escopo',
            'mudar_para_assistencia',
            'fechar_etapa',
        ];

        return in_array($value, $valid, true) ? $value : null;
    }

    protected function normalizeGap($value): ?string
    {
        $value = $this->slug($value);

        $valid = [
            'nome',
            'endereco',
            'visual',
            'medida',
            'cor_aluminio',
            'qualificacao',
            'etapa_decisao',
        ];

        return in_array($value, $valid, true) ? $value : null;
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
