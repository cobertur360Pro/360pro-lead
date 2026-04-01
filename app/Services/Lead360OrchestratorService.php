<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Schema;

class Lead360OrchestratorService
{
    public function __construct(
        protected Lead360StructuredTurnService $turnService
    ) {
    }

    public function process(Lead $lead, string $mensagem): array
    {
        $memoria = $this->readMemory($lead);

        $contexto = [
            'tipo_contato' => $memoria['tipo_contato'] ?? null,
            'nome' => $this->sanitizeLeadName($lead->nome ?: ($memoria['nome'] ?? null)),
            'telefone' => $lead->telefone ?: ($memoria['telefone'] ?? null),
            'email' => $lead->email ?: ($memoria['email'] ?? null),

            'solucao_principal' => $lead->tipo_projeto ?: ($memoria['solucao_principal'] ?? null),
            'solucao_subtipo' => $memoria['solucao_subtipo'] ?? null,

            'endereco' => $memoria['endereco'] ?? null,
            'cep' => $memoria['cep'] ?? null,
            'bairro' => $lead->bairro ?: ($memoria['bairro'] ?? null),
            'cidade' => $lead->cidade ?: ($memoria['cidade'] ?? null),

            'tipo_imovel' => $lead->tipo_imovel ?: ($memoria['tipo_imovel'] ?? null),
            'area_projeto' => $lead->interesse ?: ($memoria['area_projeto'] ?? null),

            'tem_video' => (bool) ($memoria['tem_video'] ?? false),
            'tem_projeto' => (bool) ($memoria['tem_projeto'] ?? false),
            'tem_foto' => (bool) ($memoria['tem_foto'] ?? false),

            'largura' => $lead->largura ?: ($memoria['largura'] ?? null),
            'comprimento' => $lead->comprimento ?: ($memoria['comprimento'] ?? null),

            'cor_aluminio' => $memoria['cor_aluminio'] ?? null,
            'qualificacao_prioridade' => $memoria['qualificacao_prioridade'] ?? null,
            'etapa_decisao' => $memoria['etapa_decisao'] ?? null,

            'visita_recusada' => (bool) ($memoria['visita_recusada'] ?? false),
            'lacuna_atual' => $this->resolveLacunaAtual($lead, $memoria),
            'historico' => $this->buildHistorico($lead),
        ];

        $turno = $this->turnService->process($mensagem, $contexto);
        $e = $turno['extracted'] ?? [];

        $memoryOut = array_merge($memoria, [
            'tipo_contato' => $e['tipo_contato'] ?? ($memoria['tipo_contato'] ?? null),
            'nome' => $this->sanitizeLeadName($e['nome'] ?? ($memoria['nome'] ?? null)),
            'telefone' => $e['telefone'] ?? ($memoria['telefone'] ?? null),
            'email' => $e['email'] ?? ($memoria['email'] ?? null),

            'solucao_principal' => $e['solucao_principal'] ?? ($memoria['solucao_principal'] ?? null),
            'solucao_subtipo' => $e['solucao_subtipo'] ?? ($memoria['solucao_subtipo'] ?? null),

            'endereco' => $e['endereco'] ?? ($memoria['endereco'] ?? null),
            'cep' => $e['cep'] ?? ($memoria['cep'] ?? null),
            'bairro' => $e['bairro'] ?? ($memoria['bairro'] ?? null),
            'cidade' => $e['cidade'] ?? ($memoria['cidade'] ?? null),

            'tipo_imovel' => $e['tipo_imovel'] ?? ($memoria['tipo_imovel'] ?? null),
            'area_projeto' => $e['area_projeto'] ?? ($memoria['area_projeto'] ?? null),

            'tem_video' => ! empty($e['tem_video']) || ! empty($memoria['tem_video']),
            'tem_projeto' => ! empty($e['tem_projeto']) || ! empty($memoria['tem_projeto']),
            'tem_foto' => ! empty($e['tem_foto']) || ! empty($memoria['tem_foto']),

            'largura' => $e['largura'] ?? ($memoria['largura'] ?? null),
            'comprimento' => $e['comprimento'] ?? ($memoria['comprimento'] ?? null),

            'cor_aluminio' => $e['cor_aluminio'] ?? ($memoria['cor_aluminio'] ?? null),
            'qualificacao_prioridade' => $e['qualificacao_prioridade'] ?? ($memoria['qualificacao_prioridade'] ?? null),
            'etapa_decisao' => $e['etapa_decisao'] ?? ($memoria['etapa_decisao'] ?? null),

            'visita_recusada' => ! empty($e['visit_refused']) || ! empty($memoria['visita_recusada']),
            'ultima_resposta' => $turno['reply'] ?? null,
            'understood_summary' => $turno['understood_summary'] ?? null,
        ]);

        $updates = [];

        $nomeLimpo = $this->sanitizeLeadName($memoryOut['nome'] ?? null);
        if (! empty($nomeLimpo)) {
            $updates['nome'] = $nomeLimpo;
        }

        if (! empty($memoryOut['email'])) {
            $updates['email'] = $memoryOut['email'];
        }

        if (! empty($memoryOut['bairro'])) {
            $updates['bairro'] = $memoryOut['bairro'];
        }

        if (! empty($memoryOut['cidade'])) {
            $updates['cidade'] = $memoryOut['cidade'];
        }

        if (! empty($memoryOut['solucao_principal'])) {
            $updates['tipo_projeto'] = $memoryOut['solucao_principal'];
        }

        if (! empty($memoryOut['tipo_imovel'])) {
            $updates['tipo_imovel'] = $memoryOut['tipo_imovel'];
        }

        if (! empty($memoryOut['area_projeto'])) {
            $updates['interesse'] = $memoryOut['area_projeto'];
        }

        if (! empty($memoryOut['largura'])) {
            $updates['largura'] = $memoryOut['largura'];
        }

        if (! empty($memoryOut['comprimento'])) {
            $updates['comprimento'] = $memoryOut['comprimento'];
        }

        if (Schema::hasColumn($lead->getTable(), 'resumo_contexto') && ! empty($turno['understood_summary'])) {
            $updates['resumo_contexto'] = $turno['understood_summary'];
        }

        if (! empty($updates)) {
            $lead->update($updates);
            $lead->refresh();
        }

        if (Schema::hasColumn($lead->getTable(), 'memoria_estruturada')) {
            $lead->update([
                'memoria_estruturada' => json_encode([
                    'roteiro_baumann' => true,
                    'contexto_enviado' => $contexto,
                    'turno_estruturado' => $turno,
                    'memory' => $memoryOut,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return [
            'resposta' => $turno['reply'] ?? 'Sem resposta.',
            'debug' => $turno,
        ];
    }

    protected function readMemory(Lead $lead): array
    {
        $raw = $lead->memoria_estruturada ?? null;

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return [];
        }

        return is_array($decoded['memory'] ?? null) ? $decoded['memory'] : [];
    }

    protected function buildHistorico(Lead $lead): array
    {
        if (! method_exists($lead, 'interactions')) {
            return [];
        }

        return $lead->interactions()
            ->latest('id')
            ->limit(6)
            ->get()
            ->reverse()
            ->map(function ($item) {
                return [
                    'pergunta' => (string) ($item->conteudo ?? ''),
                    'resposta' => (string) ($item->resposta_ia ?? ''),
                    'tipo' => (string) ($item->tipo ?? ''),
                ];
            })
            ->values()
            ->toArray();
    }

    protected function resolveLacunaAtual(Lead $lead, array $memory): ?string
    {
        $nome = $this->sanitizeLeadName($lead->nome ?: ($memory['nome'] ?? null));
        $tipoContato = $memory['tipo_contato'] ?? 'orcamento_produto';

        if ($tipoContato !== 'orcamento_produto') {
            return null;
        }

        if (empty($nome)) {
            return 'nome';
        }

        if (empty($memory['endereco']) && empty($memory['cep']) && empty($lead->bairro) && empty($lead->cidade) && empty($memory['bairro']) && empty($memory['cidade'])) {
            return 'endereco';
        }

        if (empty($memory['tem_video']) && empty($memory['tem_projeto']) && empty($memory['tem_foto'])) {
            return 'visual';
        }

        $largura = $lead->largura ?: ($memory['largura'] ?? null);
        $comprimento = $lead->comprimento ?: ($memory['comprimento'] ?? null);

        if (empty($largura) || empty($comprimento)) {
            return 'medida';
        }

        if (empty($memory['cor_aluminio'])) {
            return 'cor_aluminio';
        }

        if (empty($memory['qualificacao_prioridade'])) {
            return 'qualificacao';
        }

        if (empty($memory['etapa_decisao'])) {
            return 'etapa_decisao';
        }

        return null;
    }

    protected function sanitizeLeadName($name): ?string
    {
        if (! is_string($name)) {
            return null;
        }

        $name = trim($name);

        if ($name === '') {
            return null;
        }

        $normalized = $this->normalizeText($name);

        if (
            str_contains($normalized, 'teste') ||
            str_contains($normalized, 'lote') ||
            str_contains($normalized, 'debug') ||
            str_contains($name, '_')
        ) {
            return null;
        }

        return $name;
    }

    protected function normalizeText(?string $text): string
    {
        if (! is_string($text)) {
            return '';
        }

        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text) ?: $text;
        $text = strtolower($text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }
}
