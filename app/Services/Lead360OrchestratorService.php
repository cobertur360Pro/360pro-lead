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
        $contexto = [
            'nome' => $this->sanitizeLeadName($lead->nome),
            'telefone' => $lead->telefone,
            'email' => $lead->email,
            'bairro' => $lead->bairro,
            'cidade' => $lead->cidade,
            'solucao_principal' => $lead->tipo_projeto,
            'tipo_imovel' => $lead->tipo_imovel,
            'area_projeto' => $lead->interesse,
            'largura' => $lead->largura,
            'comprimento' => $lead->comprimento,
            'principal_desejo' => Schema::hasColumn($lead->getTable(), 'principal_desejo') ? $lead->principal_desejo : null,
            'prioridade_atual' => $this->normalizePrioridades($lead->prioridade_atual ?? null),
            'lacuna_atual' => $this->resolveLacunaAtual($lead),
            'historico' => $this->buildHistorico($lead),
            'visita_recusada' => $this->detectVisitRefusal($lead),
        ];

        $turno = $this->turnService->process($mensagem, $contexto);

        $extracted = $turno['extracted'] ?? [];
        $decision = $turno['decision'] ?? [];

        $updates = [];

        $nomeExtraido = $this->sanitizeLeadName($extracted['nome'] ?? null);

        if (! empty($nomeExtraido)) {
            $updates['nome'] = $nomeExtraido;
        }

        if (! empty($extracted['email'])) {
            $updates['email'] = $extracted['email'];
        }

        if (! empty($extracted['bairro'])) {
            $updates['bairro'] = $extracted['bairro'];
        }

        if (! empty($extracted['cidade'])) {
            $updates['cidade'] = $extracted['cidade'];
        }

        if (! empty($extracted['solucao_principal'])) {
            $updates['tipo_projeto'] = $extracted['solucao_principal'];
        }

        if (! empty($extracted['tipo_imovel'])) {
            $updates['tipo_imovel'] = $extracted['tipo_imovel'];
        }

        if (! empty($extracted['area_projeto'])) {
            $updates['interesse'] = $extracted['area_projeto'];
        }

        if (! empty($extracted['largura'])) {
            $updates['largura'] = $extracted['largura'];
        }

        if (! empty($extracted['comprimento'])) {
            $updates['comprimento'] = $extracted['comprimento'];
        }

        if (! empty($extracted['principal_desejo']) && Schema::hasColumn($lead->getTable(), 'principal_desejo')) {
            $updates['principal_desejo'] = $extracted['principal_desejo'];
        }

        if (! empty($extracted['objecao_principal']) && Schema::hasColumn($lead->getTable(), 'objecao_principal')) {
            $updates['objecao_principal'] = $extracted['objecao_principal'];
        }

        if (! empty($extracted['urgencia']) && Schema::hasColumn($lead->getTable(), 'urgencia_real')) {
            $updates['urgencia_real'] = $extracted['urgencia'];
        }

        if (! empty($extracted['estagio_decisao']) && Schema::hasColumn($lead->getTable(), 'fase_funil')) {
            $updates['fase_funil'] = $extracted['estagio_decisao'];
        }

        if (! empty($decision['action']) && Schema::hasColumn($lead->getTable(), 'proxima_acao')) {
            $updates['proxima_acao'] = $decision['action'];
        }

        if (! empty($turno['understood_summary']) && Schema::hasColumn($lead->getTable(), 'resumo_contexto')) {
            $updates['resumo_contexto'] = $turno['understood_summary'];
        }

        if (
            ! empty($extracted['prioridade_atual']) &&
            Schema::hasColumn($lead->getTable(), 'prioridade_atual')
        ) {
            $updates['prioridade_atual'] = json_encode(
                array_values($extracted['prioridade_atual']),
                JSON_UNESCAPED_UNICODE
            );
        }

        if (! empty($updates)) {
            $lead->update($updates);
            $lead->refresh();
        }

        if (Schema::hasColumn($lead->getTable(), 'memoria_estruturada')) {
            $lead->update([
                'memoria_estruturada' => json_encode([
                    'contexto_enviado' => $contexto,
                    'turno_estruturado' => $turno,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        return [
            'resposta' => $turno['reply'] ?? 'Sem resposta.',
            'debug' => $turno,
        ];
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

    protected function detectVisitRefusal(Lead $lead): bool
    {
        if (! method_exists($lead, 'interactions')) {
            return false;
        }

        $items = $lead->interactions()
            ->latest('id')
            ->limit(6)
            ->get();

        foreach ($items as $item) {
            $texto = $this->normalizeText((string) ($item->conteudo ?? ''));

            if (
                str_contains($texto, 'nao quero agendar visita') ||
                str_contains($texto, 'nao quero visita') ||
                str_contains($texto, 'sem visita') ||
                str_contains($texto, 'nao quero marcar visita')
            ) {
                return true;
            }
        }

        return false;
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

        if (preg_match('/lote\s*\d/i', $name)) {
            return null;
        }

        if (
            str_contains($normalized, 'teste') ||
            str_contains($normalized, 'lote7') ||
            str_contains($normalized, 'debug') ||
            str_contains($name, '_')
        ) {
            return null;
        }

        return $name;
    }

    protected function normalizePrioridades($value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $value)));
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $decoded)));
            }

            return array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $value))));
        }

        return [];
    }

    protected function resolveLacunaAtual(Lead $lead): ?string
    {
        $nome = $this->sanitizeLeadName($lead->nome);

        if (empty($nome) && $this->hasCommercialIntentInLead($lead)) {
            return 'nome';
        }

        if (empty($lead->bairro) && empty($lead->cidade)) {
            return 'localizacao';
        }

        if (empty($lead->tipo_projeto)) {
            return 'solucao_principal';
        }

        if (empty($lead->interesse)) {
            return 'area_projeto';
        }

        if (empty($lead->largura) || empty($lead->comprimento)) {
            return 'medida_ou_midia';
        }

        if (Schema::hasColumn($lead->getTable(), 'principal_desejo') && empty($lead->principal_desejo)) {
            return 'principal_desejo';
        }

        return 'prioridade_atual';
    }

    protected function hasCommercialIntentInLead(Lead $lead): bool
    {
        return ! empty($lead->tipo_projeto)
            || ! empty($lead->tipo_imovel)
            || ! empty($lead->interesse)
            || ! empty($lead->largura)
            || ! empty($lead->comprimento);
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
