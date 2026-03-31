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

        if (! $lead->tipo_imovel) {
            if (str_contains($textoLower, 'casa')) {
                $lead->tipo_imovel = 'casa';
            } elseif (str_contains($textoLower, 'apartamento')) {
                $lead->tipo_imovel = 'apartamento';
            }
        }

        if (! $lead->tipo_projeto) {
            if (str_contains($textoLower, 'cobertura')) {
                $lead->tipo_projeto = 'cobertura';
            } elseif (str_contains($textoLower, 'sacada')) {
                $lead->tipo_projeto = 'sacada';
            } elseif (str_contains($textoLower, 'fechamento')) {
                $lead->tipo_projeto = 'fechamento';
            }
        }

        if (! $lead->material_desejado) {
            if (str_contains($textoLower, 'vidro')) {
                $lead->material_desejado = 'vidro';
            } elseif (str_contains($textoLower, 'policarbonato')) {
                $lead->material_desejado = 'policarbonato';
            }
        }

        if (! $lead->estrutura_existente) {
            if (
                str_contains($textoLower, 'não tem nada') ||
                str_contains($textoLower, 'nao tem nada') ||
                str_contains($textoLower, 'não tem estrutura') ||
                str_contains($textoLower, 'nao tem estrutura') ||
                str_contains($textoLower, 'precisa fazer tudo') ||
                str_contains($textoLower, 'estrutura nova')
            ) {
                $lead->estrutura_existente = 'nao';
            } elseif (
                str_contains($textoLower, 'já tem estrutura') ||
                str_contains($textoLower, 'ja tem estrutura') ||
                str_contains($textoLower, 'tem estrutura') ||
                str_contains($textoLower, 'já existe estrutura')
            ) {
                $lead->estrutura_existente = 'sim';
            }
        }

        if (! $lead->interesse) {
            if (str_contains($textoLower, 'espaço gourmet') || str_contains($textoLower, 'espaco gourmet')) {
                $lead->interesse = 'espaco gourmet';
            } elseif (str_contains($textoLower, 'quintal')) {
                $lead->interesse = 'quintal';
            } elseif (str_contains($textoLower, 'sacada')) {
                $lead->interesse = 'sacada';
            }
        }

        if (! $lead->bairro) {
            $bairrosConhecidos = [
                'ipiranga',
                'mooca',
                'tatuape',
                'tatuapé',
                'vila prudente',
                'cambuci',
                'saude',
                'saúde',
            ];

            foreach ($bairrosConhecidos as $bairro) {
                if (str_contains($textoLower, $bairro)) {
                    $lead->bairro = $bairro;
                    break;
                }
            }
        }

        if (! $lead->largura || ! $lead->comprimento) {
            if (preg_match('/(\d+[.,]?\d*)\s*[xX]\s*(\d+[.,]?\d*)/', $textoLimpo, $matches)) {
                $lead->largura = str_replace(',', '.', $matches[1]);
                $lead->comprimento = str_replace(',', '.', $matches[2]);
            }
        }

        $lead->save();
        $lead->atualizarQualificacao();
    }
}
