<?php

namespace App\Services;

class Lead360ConversationStateService
{
    public function resolve(array $contexto, array $extracao = []): array
    {
        $merged = $this->mergeContexto($contexto, $extracao);

        if (! empty($merged['fora_escopo'])) {
            return $this->buildState('L1', $contexto['estado_atual'] ?? null, 'fora_escopo', $merged);
        }

        if (! empty($merged['assistencia'])) {
            return $this->buildState('L2', $contexto['estado_atual'] ?? null, 'assistencia', $merged);
        }

        if (! $this->hasAnyCommercialSignal($merged)) {
            return $this->buildState('E1', $contexto['estado_atual'] ?? null, 'abertura_humana', $merged);
        }

        if (empty($merged['nome'])) {
            return $this->buildState('E2', $contexto['estado_atual'] ?? null, 'nome', $merged);
        }

        if (empty($merged['cep']) && empty($merged['bairro']) && empty($merged['cidade'])) {
            return $this->buildState('E2', $contexto['estado_atual'] ?? null, 'localizacao', $merged);
        }

        if (empty($merged['solucao_principal'])) {
            return $this->buildState('E3', $contexto['estado_atual'] ?? null, 'solucao_principal', $merged);
        }

        if (empty($merged['area_projeto'])) {
            return $this->buildState('E4', $contexto['estado_atual'] ?? null, 'area_projeto', $merged);
        }

        $temTipoImovelForte = ! empty($merged['tipo_imovel']);
        $tipoImovelPodeEsperar = in_array($merged['area_projeto'] ?? '', ['garagem', 'quintal', 'fundos', 'varanda', 'varanda gourmet', 'piscina', 'jardim', 'lavanderia', 'terraco'], true);

        if (! $temTipoImovelForte && ! $tipoImovelPodeEsperar) {
            return $this->buildState('E4', $contexto['estado_atual'] ?? null, 'tipo_imovel', $merged);
        }

        $temMedida = ! empty($merged['largura']) && ! empty($merged['comprimento']);
        $temMidia = ! empty($merged['tem_foto']) || ! empty($merged['tem_video']) || ! empty($merged['tem_projeto']);

        if (! $temMedida && ! $temMidia) {
            return $this->buildState('E5', $contexto['estado_atual'] ?? null, 'medida_ou_midia', $merged);
        }

        if (empty($merged['principal_desejo'])) {
            return $this->buildState('E6', $contexto['estado_atual'] ?? null, 'principal_desejo', $merged);
        }

        if (empty($merged['prioridade_atual'])) {
            return $this->buildState('E6', $contexto['estado_atual'] ?? null, 'prioridade_atual', $merged);
        }

        if ($this->isReadyForHandoff($merged)) {
            return $this->buildState('E8', $contexto['estado_atual'] ?? null, 'handoff', $merged);
        }

        return $this->buildState('E7', $contexto['estado_atual'] ?? null, 'orientacao_estrategica', $merged);
    }

    protected function mergeContexto(array $contexto, array $extracao): array
    {
        $merged = $contexto;

        foreach ($extracao as $chave => $valor) {
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

        if (! isset($merged['prioridade_atual']) || ! is_array($merged['prioridade_atual'])) {
            $merged['prioridade_atual'] = [];
        }

        return $merged;
    }

    protected function buildState(string $estadoAtual, ?string $estadoAnterior, string $lacunaAtual, array $merged): array
    {
        $lacunasFechadas = $this->collectLacunasFechadas($merged);
        $lacunasPendentes = $this->collectLacunasPendentes($merged);

        return [
            'estado_atual' => $estadoAtual,
            'estado_anterior' => $estadoAnterior,
            'lacuna_atual' => $lacunaAtual,
            'lacunas_fechadas' => $lacunasFechadas,
            'lacunas_pendentes' => $lacunasPendentes,
            'pode_avancar' => ! in_array($lacunaAtual, $lacunasPendentes, true),
            'pede_confirmacao' => in_array('nome', $merged['campos_baixa_confianca'] ?? [], true),
            'proxima_acao' => $this->suggestAction($estadoAtual, $lacunaAtual),
            'resumo_estado' => $this->buildResumoEstado($estadoAtual, $lacunaAtual, $merged),
        ];
    }

    protected function collectLacunasFechadas(array $merged): array
    {
        $fechadas = [];

        if (! empty($merged['nome'])) {
            $fechadas[] = 'nome';
        }

        if (! empty($merged['cep']) || ! empty($merged['bairro']) || ! empty($merged['cidade'])) {
            $fechadas[] = 'localizacao';
        }

        if (! empty($merged['solucao_principal'])) {
            $fechadas[] = 'solucao_principal';
        }

        if (! empty($merged['tipo_imovel'])) {
            $fechadas[] = 'tipo_imovel';
        }

        if (! empty($merged['area_projeto'])) {
            $fechadas[] = 'area_projeto';
        }

        if ((! empty($merged['largura']) && ! empty($merged['comprimento'])) || ! empty($merged['tem_foto']) || ! empty($merged['tem_video']) || ! empty($merged['tem_projeto'])) {
            $fechadas[] = 'medida_ou_midia';
        }

        if (! empty($merged['principal_desejo'])) {
            $fechadas[] = 'principal_desejo';
        }

        if (! empty($merged['prioridade_atual'])) {
            $fechadas[] = 'prioridade_atual';
        }

        return $fechadas;
    }

    protected function collectLacunasPendentes(array $merged): array
    {
        $pendentes = [];

        if (empty($merged['nome'])) {
            $pendentes[] = 'nome';
        }

        if (empty($merged['cep']) && empty($merged['bairro']) && empty($merged['cidade'])) {
            $pendentes[] = 'localizacao';
        }

        if (empty($merged['solucao_principal'])) {
            $pendentes[] = 'solucao_principal';
        }

        if (empty($merged['area_projeto'])) {
            $pendentes[] = 'area_projeto';
        }

        $tipoImovelPodeEsperar = in_array($merged['area_projeto'] ?? '', ['garagem', 'quintal', 'fundos', 'varanda', 'varanda gourmet', 'piscina', 'jardim', 'lavanderia', 'terraco'], true);

        if (empty($merged['tipo_imovel']) && ! $tipoImovelPodeEsperar) {
            $pendentes[] = 'tipo_imovel';
        }

        if (
            (empty($merged['largura']) || empty($merged['comprimento'])) &&
            empty($merged['tem_foto']) &&
            empty($merged['tem_video']) &&
            empty($merged['tem_projeto'])
        ) {
            $pendentes[] = 'medida_ou_midia';
        }

        if (empty($merged['principal_desejo'])) {
            $pendentes[] = 'principal_desejo';
        }

        if (empty($merged['prioridade_atual'])) {
            $pendentes[] = 'prioridade_atual';
        }

        return $pendentes;
    }

    protected function hasAnyCommercialSignal(array $merged): bool
    {
        $campos = [
            'nome',
            'solucao_principal',
            'tipo_imovel',
            'area_projeto',
            'largura',
            'comprimento',
            'bairro',
            'cidade',
            'cep',
        ];

        foreach ($campos as $campo) {
            if (! empty($merged[$campo])) {
                return true;
            }
        }

        return false;
    }

    protected function isReadyForHandoff(array $merged): bool
    {
        return ! empty($merged['nome'])
            && (! empty($merged['cep']) || ! empty($merged['bairro']) || ! empty($merged['cidade']))
            && ! empty($merged['solucao_principal'])
            && ! empty($merged['area_projeto'])
            && (
                (! empty($merged['largura']) && ! empty($merged['comprimento']))
                || ! empty($merged['tem_foto'])
                || ! empty($merged['tem_video'])
                || ! empty($merged['tem_projeto'])
            )
            && ! empty($merged['principal_desejo'])
            && ! empty($merged['prioridade_atual']);
    }

    protected function suggestAction(string $estadoAtual, string $lacunaAtual): string
    {
        return match ($estadoAtual) {
            'L1' => 'bloquear_fora_escopo',
            'L2' => 'mudar_para_assistencia',
            'E1' => 'acolher',
            'E2', 'E3', 'E4', 'E6' => 'perguntar',
            'E5' => 'pedir_material_apoio',
            'E7' => 'orientar',
            'E8' => 'encaminhar_humano',
            default => 'acolher',
        };
    }

    protected function buildResumoEstado(string $estadoAtual, string $lacunaAtual, array $merged): string
    {
        return match ($estadoAtual) {
            'L1' => 'Lead fora de escopo comercial da Baumann.',
            'L2' => 'Lead em trilha de assistência ou pós-venda.',
            'E1' => 'Conversa ainda em abertura humana.',
            'E2' => 'Identificação mínima ainda incompleta. Lacuna dominante: ' . $lacunaAtual . '.',
            'E3' => 'Solução desejada ainda não definida.',
            'E4' => 'Contexto do projeto ainda incompleto. Lacuna dominante: ' . $lacunaAtual . '.',
            'E5' => 'Falta medida ou mídia para orientar com segurança.',
            'E6' => 'Base técnica mínima já existe; falta entendimento comercial.',
            'E7' => 'Lead pronto para orientação estratégica.',
            'E8' => 'Lead maduro para handoff ou próxima ação operacional.',
            default => 'Estado não classificado.',
        };
    }
}
