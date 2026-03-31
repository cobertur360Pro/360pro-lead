<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Str;

class LeadMemoryExtractorService
{
    public function extrairEAtualizar(Lead $lead, string $texto): void
    {
        $textoLimpo = trim($texto);
        $textoLower = Str::lower($textoLimpo);

        $this->extrairTipoImovel($lead, $textoLower);
        $this->extrairTipoProjeto($lead, $textoLower);
        $this->extrairMaterialDesejado($lead, $textoLower);
        $this->extrairEstruturaExistente($lead, $textoLower);
        $this->extrairInteresse($lead, $textoLower);
        $this->extrairBairro($lead, $textoLower);
        $this->extrairMedidas($lead, $textoLimpo, $textoLower);
        $this->extrairIndicadorFotos($lead, $textoLower);

        $lead->save();
        $lead->atualizarQualificacao();
    }

    protected function extrairTipoImovel(Lead $lead, string $textoLower): void
    {
        if ($lead->tipo_imovel) {
            return;
        }

        if (str_contains($textoLower, 'casa')) {
            $lead->tipo_imovel = 'casa';
            return;
        }

        if (str_contains($textoLower, 'apartamento')) {
            $lead->tipo_imovel = 'apartamento';
            return;
        }

        if (
            str_contains($textoLower, 'comercial') ||
            str_contains($textoLower, 'empresa') ||
            str_contains($textoLower, 'faculdade') ||
            str_contains($textoLower, 'estúdio') ||
            str_contains($textoLower, 'estudio')
        ) {
            $lead->tipo_imovel = 'comercial';
        }
    }

    protected function extrairTipoProjeto(Lead $lead, string $textoLower): void
    {
        if (! $lead->tipo_projeto) {
            if (str_contains($textoLower, 'cobertura')) {
                $lead->tipo_projeto = 'cobertura';
                return;
            }

            if (str_contains($textoLower, 'sacada')) {
                $lead->tipo_projeto = 'sacada';
                return;
            }

            if (str_contains($textoLower, 'fechamento')) {
                $lead->tipo_projeto = 'fechamento';
                return;
            }

            if (str_contains($textoLower, 'box')) {
                $lead->tipo_projeto = 'box';
            }
        }
    }

    protected function extrairMaterialDesejado(Lead $lead, string $textoLower): void
    {
        if ($lead->material_desejado) {
            return;
        }

        if (str_contains($textoLower, 'vidro')) {
            $lead->material_desejado = 'vidro';
            return;
        }

        if (str_contains($textoLower, 'policarbonato')) {
            $lead->material_desejado = 'policarbonato';
            return;
        }

        if (str_contains($textoLower, 'telha sanduíche') || str_contains($textoLower, 'telha sanduiche')) {
            $lead->material_desejado = 'telha sanduiche';
        }
    }

    protected function extrairEstruturaExistente(Lead $lead, string $textoLower): void
    {
        if ($lead->estrutura_existente) {
            return;
        }

        if (
            str_contains($textoLower, 'não tem nada') ||
            str_contains($textoLower, 'nao tem nada') ||
            str_contains($textoLower, 'não tem estrutura') ||
            str_contains($textoLower, 'nao tem estrutura') ||
            str_contains($textoLower, 'estrutura nova')
        ) {
            $lead->estrutura_existente = 'nao';
            return;
        }

        if (
            str_contains($textoLower, 'já tem estrutura') ||
            str_contains($textoLower, 'ja tem estrutura') ||
            str_contains($textoLower, 'tem estrutura')
        ) {
            $lead->estrutura_existente = 'sim';
        }
    }

    protected function extrairInteresse(Lead $lead, string $textoLower): void
    {
        if ($lead->interesse) {
            return;
        }

        if (str_contains($textoLower, 'quintal')) {
            $lead->interesse = 'quintal';
            return;
        }

        if (str_contains($textoLower, 'espaço gourmet') || str_contains($textoLower, 'espaco gourmet')) {
            $lead->interesse = 'espaco gourmet';
            return;
        }

        if (str_contains($textoLower, 'sacada')) {
            $lead->interesse = 'sacada';
            return;
        }

        if (str_contains($textoLower, 'piscina')) {
            $lead->interesse = 'piscina';
        }
    }

    protected function extrairBairro(Lead $lead, string $textoLower): void
    {
        if ($lead->bairro) {
            return;
        }

        $bairrosConhecidos = [
            'ipiranga',
            'mooca',
            'tatuape',
            'tatuapé',
            'vila prudente',
            'cambuci',
            'saude',
            'saúde',
            'itaim bibi',
            'brooklin',
            'vila ipojuca',
            'jardim da saúde',
            'jardim da saude',
        ];

        foreach ($bairrosConhecidos as $bairro) {
            if (str_contains($textoLower, $bairro)) {
                $lead->bairro = $bairro;
                return;
            }
        }
    }

    protected function extrairMedidas(Lead $lead, string $textoLimpo, string $textoLower): void
    {
        if ($lead->largura && $lead->comprimento) {
            return;
        }

        if (preg_match('/(\d+[.,]?\d*)\s*[xX]\s*(\d+[.,]?\d*)/', $textoLimpo, $matches)) {
            $lead->largura = str_replace(',', '.', $matches[1]);
            $lead->comprimento = str_replace(',', '.', $matches[2]);
            return;
        }

        if (preg_match('/(\d+[.,]?\d*)\s*por\s*(\d+[.,]?\d*)/i', $textoLower, $matches)) {
            $lead->largura = str_replace(',', '.', $matches[1]);
            $lead->comprimento = str_replace(',', '.', $matches[2]);
        }
    }

    protected function extrairIndicadorFotos(Lead $lead, string $textoLower): void
    {
        if (
            str_contains($textoLower, 'enviei foto') ||
            str_contains($textoLower, 'mande foto') ||
            str_contains($textoLower, 'mandei foto') ||
            str_contains($textoLower, 'enviei vídeo') ||
            str_contains($textoLower, 'enviei video') ||
            str_contains($textoLower, 'mandei vídeo') ||
            str_contains($textoLower, 'mandei video')
        ) {
            $observacao = trim(($lead->observacoes ?? '') . ' | cliente_mencionou_fotos');
            $lead->observacoes = ltrim($observacao, ' |');
        }
    }
}
