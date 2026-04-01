<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Collection;

class Lead360ContextBuilderService
{
    public function build(Lead $lead, array $overrides = []): array
    {
        $historico = $this->buildHistorico($lead);

        $contexto = [
            'lead_id' => $lead->id,
            'nome' => $this->stringOrNull($lead->nome ?? null),
            'telefone' => $this->stringOrNull($lead->telefone ?? null),
            'email' => $this->stringOrNull($lead->email ?? null),
            'perfil_contato' => $this->stringOrNull($lead->perfil_cliente ?? null),

            'cep' => $this->extractCepFromLead($lead),
            'endereco' => $this->extractEnderecoFromLead($lead),
            'bairro' => $this->stringOrNull($lead->bairro ?? null),
            'cidade' => $this->stringOrNull($lead->cidade ?? null),

            'solucao_principal' => $this->stringOrNull($lead->tipo_projeto ?? null),
            'solucao_subtipo' => $this->stringOrNull($lead->subtipo_projeto ?? null),

            'tipo_imovel' => $this->stringOrNull($lead->tipo_imovel ?? null),
            'area_projeto' => $this->stringOrNull($lead->interesse ?? null),
            'contexto_uso' => $this->stringOrNull($lead->motivo_compra ?? null),

            'largura' => $this->numberLikeOrNull($lead->largura ?? null),
            'comprimento' => $this->numberLikeOrNull($lead->comprimento ?? null),
            'area_informada_m2' => $this->numberLikeOrNull($lead->area_informada_m2 ?? null),

            'tem_foto' => $this->boolFromLead($lead, ['tem_foto', 'cliente_enviou_foto']),
            'tem_video' => $this->boolFromLead($lead, ['tem_video', 'cliente_enviou_video']),
            'tem_projeto' => $this->boolFromLead($lead, ['tem_projeto', 'cliente_enviou_projeto']),
            'midiapendente' => $this->boolFromLead($lead, ['midiapendente', 'midia_pendente']),

            'principal_desejo' => $this->stringOrNull($lead->principal_desejo ?? null),
            'prioridade_atual' => $this->normalizePrioridades($lead->prioridade_atual ?? null),
            'urgencia' => $this->stringOrNull($lead->urgencia_real ?? null),
            'objecao_principal' => $this->stringOrNull($lead->objecao_principal ?? null),
            'estagio_decisao' => $this->stringOrNull($lead->fase_funil ?? null),

            'estado_atual' => null,
            'lacuna_atual' => null,
            'proxima_acao' => $this->stringOrNull($lead->proxima_acao ?? null),
            'resumo_contexto' => null,

            'historico' => $historico,
        ];

        $contexto = array_merge($contexto, $overrides);

        if (empty($contexto['resumo_contexto'])) {
            $contexto['resumo_contexto'] = $this->buildResumoContexto($contexto);
        }

        return $contexto;
    }

    protected function buildHistorico(Lead $lead): array
    {
        if (! method_exists($lead, 'interactions')) {
            return [];
        }

        $query = $lead->interactions();

        $items = $query
            ->latest('id')
            ->limit(8)
            ->get()
            ->reverse()
            ->values();

        return $items->map(function ($item) {
            return [
                'pergunta' => (string) ($item->conteudo ?? ''),
                'resposta' => (string) ($item->resposta_ia ?? ''),
                'tipo' => (string) ($item->tipo ?? ''),
            ];
        })->toArray();
    }

    protected function buildResumoContexto(array $contexto): string
    {
        $partes = [];

        if (! empty($contexto['nome'])) {
            $partes[] = 'Lead ' . $contexto['nome'];
        }

        if (! empty($contexto['solucao_principal'])) {
            $partes[] = 'buscando ' . $contexto['solucao_principal'];
        }

        if (! empty($contexto['area_projeto'])) {
            $partes[] = 'para ' . $contexto['area_projeto'];
        }

        if (! empty($contexto['tipo_imovel'])) {
            $partes[] = 'em ' . $contexto['tipo_imovel'];
        }

        if (! empty($contexto['bairro'])) {
            $partes[] = 'no bairro ' . $contexto['bairro'];
        } elseif (! empty($contexto['cidade'])) {
            $partes[] = 'na cidade ' . $contexto['cidade'];
        }

        if (! empty($contexto['largura']) && ! empty($contexto['comprimento'])) {
            $partes[] = 'com medida aproximada de ' . $contexto['largura'] . ' x ' . $contexto['comprimento'];
        }

        if (! empty($contexto['tem_foto']) || ! empty($contexto['tem_video']) || ! empty($contexto['tem_projeto'])) {
            $midias = [];

            if (! empty($contexto['tem_foto'])) {
                $midias[] = 'foto';
            }

            if (! empty($contexto['tem_video'])) {
                $midias[] = 'vídeo';
            }

            if (! empty($contexto['tem_projeto'])) {
                $midias[] = 'projeto';
            }

            $partes[] = 'com ' . implode(', ', $midias) . ' enviado';
        }

        if (! empty($contexto['principal_desejo'])) {
            $partes[] = 'principal desejo: ' . $contexto['principal_desejo'];
        }

        if (! empty($contexto['urgencia'])) {
            $partes[] = 'urgência ' . $contexto['urgencia'];
        }

        if (! empty($contexto['objecao_principal'])) {
            $partes[] = 'objeção principal: ' . $contexto['objecao_principal'];
        }

        return empty($partes)
            ? 'Lead ainda com pouco contexto comercial preenchido.'
            : ucfirst(implode(', ', $partes)) . '.';
    }

    protected function normalizePrioridades($prioridades): array
    {
        if (is_array($prioridades)) {
            return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $prioridades)));
        }

        if (is_string($prioridades) && trim($prioridades) !== '') {
            return array_values(array_filter(array_map('trim', preg_split('/[,;|]/', $prioridades))));
        }

        return [];
    }

    protected function extractCepFromLead(Lead $lead): ?string
    {
        $campos = ['cep', 'cep_local', 'cep_obra'];

        foreach ($campos as $campo) {
            if (isset($lead->{$campo}) && trim((string) $lead->{$campo}) !== '') {
                return trim((string) $lead->{$campo});
            }
        }

        return null;
    }

    protected function extractEnderecoFromLead(Lead $lead): ?string
    {
        $campos = ['endereco', 'logradouro', 'endereco_obra'];

        foreach ($campos as $campo) {
            if (isset($lead->{$campo}) && trim((string) $lead->{$campo}) !== '') {
                return trim((string) $lead->{$campo});
            }
        }

        return null;
    }

    protected function boolFromLead(Lead $lead, array $campos): bool
    {
        foreach ($campos as $campo) {
            if (! isset($lead->{$campo})) {
                continue;
            }

            $valor = $lead->{$campo};

            if (is_bool($valor)) {
                return $valor;
            }

            if (in_array((string) $valor, ['1', 'true', 'sim', 'yes'], true)) {
                return true;
            }
        }

        return false;
    }

    protected function stringOrNull($value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    protected function numberLikeOrNull($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }
}
