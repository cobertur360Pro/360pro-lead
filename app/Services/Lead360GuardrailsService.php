<?php

namespace App\Services;

class Lead360GuardrailsService
{
    public function atendimentoIaHabilitado(): bool
    {
        return modulo_habilitado('LD001') && param_bool('LDA-001');
    }

    public function qualificacaoHabilitada(): bool
    {
        return modulo_habilitado('LD002') && param_bool('LDA-002');
    }

    public function memoriaHabilitada(): bool
    {
        return modulo_habilitado('LD003') && param_bool('LDA-003');
    }

    public function openAiHabilitada(): bool
    {
        return modulo_habilitado('LD008') && param_bool('LDA-010');
    }

    public function whatsappHabilitado(): bool
    {
        return modulo_habilitado('LD007') && param_bool('LDA-011');
    }

    public function kommoHabilitado(): bool
    {
        return modulo_habilitado('LD009') && param_bool('LDA-012');
    }

    public function cobertura360Habilitado(): bool
    {
        return modulo_habilitado('LD010') && param_bool('LDA-013');
    }

    public function iaPodeAgendarDiretamente(): bool
    {
        return param_bool('LDA-020');
    }

    public function iaPodeGerarOrcamentoDiretamente(): bool
    {
        return param_bool('LDA-021');
    }

    public function iaPodeFalarPrecoSemContexto(): bool
    {
        return param_bool('LDA-022');
    }

    public function iaPodeEncaminharHumano(): bool
    {
        return param_bool('LDA-023', true);
    }

    public function exigeFotosParaOrcamento(): bool
    {
        return param_bool('LDA-024', true);
    }

    public function exigeMedidasParaOrcamento(): bool
    {
        return param_bool('LDA-025', true);
    }

    public function fluxoGuiadoAtivo(): bool
    {
        return param_bool('LDA-034', true);
    }

    public function validarContextoMinimoParaOrcamento(array $contexto): array
    {
        $faltando = [];

        if ($this->exigeMedidasParaOrcamento()) {
            if (empty($contexto['largura']) || empty($contexto['comprimento'])) {
                $faltando[] = 'medidas aproximadas';
            }
        }

        if ($this->exigeFotosParaOrcamento()) {
            if (empty($contexto['tem_fotos'])) {
                $faltando[] = 'fotos ou vídeo do local';
            }
        }

        if (empty($contexto['tipo_imovel'])) {
            $faltando[] = 'tipo de imóvel';
        }

        if (empty($contexto['tipo_projeto'])) {
            $faltando[] = 'tipo de projeto';
        }

        return $faltando;
    }

    public function produtoPermitido(string $mensagem): bool
    {
        $texto = mb_strtolower($mensagem);

        $produtosForaEscopo = [
            'toldo',
            'telha de barro',
            'madeira com telha',
            'pergolado de madeira',
        ];

        foreach ($produtosForaEscopo as $produto) {
            if (str_contains($texto, $produto)) {
                return false;
            }
        }

        return true;
    }

    public function mensagemProdutoForaEscopo(): string
    {
        return 'Hoje nosso atendimento está focado nas soluções que fazem parte do escopo da Baumann, como coberturas em vidro, policarbonato, envidraçamentos e estruturas relacionadas. Esse item específico não faz parte da nossa linha atual.';
    }

    public function mensagemAtendimentoDesabilitado(): string
    {
        return 'O atendimento automático está desativado para esta empresa no momento.';
    }
}
