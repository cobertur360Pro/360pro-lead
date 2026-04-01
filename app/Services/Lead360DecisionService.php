<?php

namespace App\Services;

class Lead360DecisionService
{
    public function decide(array $contexto, array $extracao, array $estado): array
    {
        $acao = $this->resolveAction($contexto, $extracao, $estado);

        return [
            'acao_principal' => $acao,
            'motivo_decisao' => $this->resolveReason($acao, $contexto, $extracao, $estado),
            'lacuna_tratada' => $estado['lacuna_atual'] ?? null,
            'lacuna_seguinte' => $this->resolveNextGap($acao, $estado),
            'usa_redacao_curta' => $this->usesShortWriting($acao, $extracao),
            'precisa_confirmacao' => (bool) ($estado['pede_confirmacao'] ?? false),
            'deve_encaminhar' => in_array($acao, ['encaminhar_humano', 'encaminhar_visita'], true),
        ];
    }

    protected function resolveAction(array $contexto, array $extracao, array $estado): string
    {
        if (! empty($extracao['fora_escopo'])) {
            return 'bloquear_fora_escopo';
        }

        if (! empty($extracao['assistencia'])) {
            return 'mudar_para_assistencia';
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'saudacao' && ! $this->hasRelevantCommercialData($extracao)) {
            return 'acolher';
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'pedido_preco') {
            if (! $this->hasMinimumForFastBudget($contexto, $extracao)) {
                return 'segurar_preco';
            }

            return 'orientar';
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'pedido_prazo') {
            return 'segurar_prazo';
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'pedido_visita') {
            return 'encaminhar_visita';
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'objecao') {
            return 'defender_valor';
        }

        if (($estado['estado_atual'] ?? null) === 'E8') {
            return 'encaminhar_humano';
        }

        if (($estado['estado_atual'] ?? null) === 'E7') {
            return 'orientar';
        }

        if (($estado['estado_atual'] ?? null) === 'E5') {
            return 'pedir_material_apoio';
        }

        if (($estado['estado_atual'] ?? null) === 'E1') {
            return 'acolher';
        }

        if (in_array(($estado['estado_atual'] ?? null), ['E2', 'E3', 'E4', 'E6'], true)) {
            return 'perguntar';
        }

        if (! empty($extracao['trouxe_dado_novo']) && ! empty($estado['lacuna_atual'])) {
            return 'perguntar';
        }

        return 'acolher';
    }

    protected function resolveReason(string $acao, array $contexto, array $extracao, array $estado): string
    {
        return match ($acao) {
            'bloquear_fora_escopo' => 'Mensagem identificada como fora do escopo comercial da Baumann.',
            'mudar_para_assistencia' => 'Mensagem indica assistência, manutenção ou pós-venda.',
            'acolher' => 'Conversa ainda está em abertura ou precisa de acolhimento inicial.',
            'segurar_preco' => 'Cliente pediu preço antes do mínimo necessário para orientar com segurança.',
            'segurar_prazo' => 'Cliente pediu prazo e a resposta precisa tratar prazo como variável.',
            'encaminhar_visita' => 'Cliente pediu visita ou o caso indica necessidade de validação em campo.',
            'defender_valor' => 'Mensagem trouxe objeção comercial, principalmente preço.',
            'encaminhar_humano' => 'Lead já tem base suficiente e está pronto para próxima ação humana.',
            'orientar' => 'Lead já tem contexto suficiente para receber orientação consultiva.',
            'pedir_material_apoio' => 'Falta medida, foto, vídeo ou projeto para seguir com segurança.',
            'perguntar' => 'Ainda existe uma lacuna dominante aberta no fluxo comercial.',
            default => 'Ação escolhida pela combinação de estado, extração e contexto atual.',
        };
    }

    protected function resolveNextGap(string $acao, array $estado): ?string
    {
        if (in_array($acao, ['encaminhar_humano', 'encaminhar_visita', 'bloquear_fora_escopo', 'mudar_para_assistencia'], true)) {
            return null;
        }

        return $estado['lacuna_atual'] ?? null;
    }

    protected function usesShortWriting(string $acao, array $extracao): bool
    {
        if (in_array($acao, ['defender_valor', 'orientar', 'segurar_preco', 'segurar_prazo'], true)) {
            return false;
        }

        if (($extracao['tipo_mensagem'] ?? null) === 'resposta_curta') {
            return true;
        }

        return true;
    }

    protected function hasRelevantCommercialData(array $extracao): bool
    {
        $campos = [
            'nome',
            'cep',
            'bairro',
            'cidade',
            'solucao_principal',
            'tipo_imovel',
            'area_projeto',
            'largura',
            'comprimento',
            'principal_desejo',
            'objecao_principal',
        ];

        foreach ($campos as $campo) {
            if (! empty($extracao[$campo])) {
                return true;
            }
        }

        if (! empty($extracao['tem_foto']) || ! empty($extracao['tem_video']) || ! empty($extracao['tem_projeto'])) {
            return true;
        }

        if (! empty($extracao['prioridade_atual'])) {
            return true;
        }

        return false;
    }

    protected function hasMinimumForFastBudget(array $contexto, array $extracao): bool
    {
        $merged = $this->mergeContexto($contexto, $extracao);

        $temNome = ! empty($merged['nome']);
        $temLocalizacao = ! empty($merged['cep']) || ! empty($merged['bairro']) || ! empty($merged['cidade']);
        $temSolucao = ! empty($merged['solucao_principal']);
        $temArea = ! empty($merged['area_projeto']);
        $temMedidaOuMidia = (
            (! empty($merged['largura']) && ! empty($merged['comprimento']))
            || ! empty($merged['tem_foto'])
            || ! empty($merged['tem_video'])
            || ! empty($merged['tem_projeto'])
        );

        return $temNome && $temLocalizacao && $temSolucao && $temArea && $temMedidaOuMidia;
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

        return $merged;
    }
}
