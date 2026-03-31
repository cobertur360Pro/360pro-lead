<?php

namespace App\Services;

use App\Models\Lead;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LeadDecisionEngineService
{
    public function processar(Lead $lead, string $texto): void
    {
        $textoLower = Str::lower(trim($texto));

        $this->definirPerfil($lead, $textoLower);
        $this->definirUrgenciaReal($lead, $textoLower);
        $this->definirFaseFunil($lead, $textoLower);
        $this->definirPreferenciasEObjecoes($lead, $textoLower);
        $this->definirProximaAcao($lead, $textoLower);
        $this->gerarResumoContexto($lead);

        $lead->save();
        $lead->atualizarQualificacao();
    }

    protected function definirPerfil(Lead $lead, string $textoLower): void
    {
        if (str_contains($textoLower, 'arquiteta') || str_contains($textoLower, 'arquiteto')) {
            $lead->perfil_cliente = 'arquiteto';
            $lead->cliente_tecnico = true;
        } elseif (
            str_contains($textoLower, 'engenheiro') ||
            str_contains($textoLower, 'art') ||
            str_contains($textoLower, 'projeto') ||
            str_contains($textoLower, 'planta')
        ) {
            if (! $lead->perfil_cliente) {
                $lead->perfil_cliente = 'tecnico';
            }
            $lead->cliente_tecnico = true;
        } elseif (
            str_contains($textoLower, 'faculdade') ||
            str_contains($textoLower, 'empresa') ||
            str_contains($textoLower, 'cliente comercial') ||
            str_contains($textoLower, 'estúdio') ||
            str_contains($textoLower, 'estudio')
        ) {
            $lead->perfil_cliente = 'corporativo';
        } elseif (! $lead->perfil_cliente) {
            $lead->perfil_cliente = 'comum';
        }
    }

    protected function definirUrgenciaReal(Lead $lead, string $textoLower): void
    {
        if (
            str_contains($textoLower, 'urgência') ||
            str_contains($textoLower, 'urgencia') ||
            str_contains($textoLower, 'o mais rápido possível') ||
            str_contains($textoLower, 'temos pressa') ||
            str_contains($textoLower, 'segunda semana de fevereiro') ||
            str_contains($textoLower, 'inauguração') ||
            str_contains($textoLower, 'aulas começam') ||
            str_contains($textoLower, 'precisa estar pronto')
        ) {
            $lead->urgencia_real = 'alta';
        } elseif (
            str_contains($textoLower, 'sem pressa') ||
            str_contains($textoLower, 'no futuro') ||
            str_contains($textoLower, 'adiantando orçamento') ||
            str_contains($textoLower, 'ainda não recebi as chaves')
        ) {
            $lead->urgencia_real = 'baixa';
        } elseif (! $lead->urgencia_real) {
            $lead->urgencia_real = 'media';
        }
    }

    protected function definirFaseFunil(Lead $lead, string $textoLower): void
    {
        if (
            str_contains($textoLower, 'vazamento') ||
            str_contains($textoLower, 'regulagem') ||
            str_contains($textoLower, 'garantia') ||
            str_contains($textoLower, 'assistência')
        ) {
            $lead->fase_funil = 'assistencia';
            return;
        }

        if (
            str_contains($textoLower, 'instalação') ||
            str_contains($textoLower, 'estrutura') ||
            str_contains($textoLower, 'obra') ||
            str_contains($textoLower, 'medição') ||
            str_contains($textoLower, 'medição')
        ) {
            $lead->fase_funil = 'obra';
            return;
        }

        if (
            str_contains($textoLower, 'fechar') ||
            str_contains($textoLower, 'contrato') ||
            str_contains($textoLower, 'cnpj') ||
            str_contains($textoLower, 'vou seguir') ||
            str_contains($textoLower, 'aprovar')
        ) {
            $lead->fase_funil = 'negociacao';
            return;
        }

        if (
            str_contains($textoLower, 'orçamento') ||
            str_contains($textoLower, 'orcamento') ||
            str_contains($textoLower, 'proposta') ||
            str_contains($textoLower, 'valor')
        ) {
            if (! $lead->fase_funil || $lead->fase_funil === 'entrada') {
                $lead->fase_funil = 'proposta';
            }
            return;
        }

        if (
            str_contains($textoLower, 'foto') ||
            str_contains($textoLower, 'vídeo') ||
            str_contains($textoLower, 'video') ||
            str_contains($textoLower, 'planta') ||
            str_contains($textoLower, 'medida')
        ) {
            if (! $lead->fase_funil) {
                $lead->fase_funil = 'diagnostico';
            }
            return;
        }

        if (! $lead->fase_funil) {
            $lead->fase_funil = 'entrada';
        }
    }

    protected function definirPreferenciasEObjecoes(Lead $lead, string $textoLower): void
    {
        if (
            str_contains($textoLower, 'fosco') ||
            str_contains($textoLower, 'mais natural') ||
            str_contains($textoLower, 'não gosto de pvc brilhante') ||
            str_contains($textoLower, 'nao gosto de pvc brilhante')
        ) {
            $lead->preferencia_estetica = 'acabamento fosco/natural';
        }

        if (
            str_contains($textoLower, 'estufa') ||
            str_contains($textoLower, 'isolasse o calor') ||
            str_contains($textoLower, 'isolamento térmico') ||
            str_contains($textoLower, 'isolamento termico')
        ) {
            $lead->medo_principal = 'calor excessivo';
        }

        if (
            str_contains($textoLower, 'está acima') ||
            str_contains($textoLower, 'esta acima') ||
            str_contains($textoLower, 'preço conta muito') ||
            str_contains($textoLower, 'preco conta muito') ||
            str_contains($textoLower, 'mais em conta') ||
            str_contains($textoLower, 'melhor preço') ||
            str_contains($textoLower, 'melhor preco')
        ) {
            $lead->objecao_principal = 'preco';
            $lead->restricao_orcamento = 'sim';
        }

        if (
            str_contains($textoLower, 'segunda semana de fevereiro') ||
            str_contains($textoLower, 'até fevereiro') ||
            str_contains($textoLower, 'ate fevereiro') ||
            str_contains($textoLower, 'prazo curto')
        ) {
            $lead->restricao_prazo = 'sim';
        }

        if (
            str_contains($textoLower, 'segurança') ||
            str_contains($textoLower, 'seguranca') ||
            str_contains($textoLower, 'chuvas de hoje em dia') ||
            str_contains($textoLower, 'ventos fortes')
        ) {
            $lead->motivo_compra = 'seguranca/protecao';
        }

        if (
            str_contains($textoLower, 'ar livre') ||
            str_contains($textoLower, 'céu com estrelas') ||
            str_contains($textoLower, 'ceu com estrelas')
        ) {
            $lead->motivo_compra = 'conforto/experiencia';
        }
    }

    protected function definirProximaAcao(Lead $lead, string $textoLower): void
    {
        if ($lead->fase_funil === 'entrada' || $lead->fase_funil === 'diagnostico') {
            $lead->proxima_acao = 'coletar dados do projeto';
            $lead->data_followup = Carbon::now()->addDays(1)->toDateString();
            return;
        }

        if ($lead->fase_funil === 'proposta') {
            $lead->proxima_acao = 'explicar proposta e conduzir decisao';
            $lead->data_followup = Carbon::now()->addDays(2)->toDateString();
            return;
        }

        if ($lead->fase_funil === 'negociacao') {
            $lead->proxima_acao = 'travar escopo e fechar';
            $lead->data_followup = Carbon::now()->addDay()->toDateString();
            return;
        }

        if ($lead->fase_funil === 'obra') {
            $lead->proxima_acao = 'atualizar cronograma';
            $lead->data_followup = Carbon::now()->addDay()->toDateString();
            return;
        }

        if ($lead->fase_funil === 'assistencia') {
            $lead->proxima_acao = 'registrar problema e dar retorno com prazo';
            $lead->data_followup = Carbon::now()->addDay()->toDateString();
        }
    }

    protected function gerarResumoContexto(Lead $lead): void
    {
        $partes = [];

        if ($lead->perfil_cliente) $partes[] = 'Perfil: ' . $lead->perfil_cliente;
        if ($lead->fase_funil) $partes[] = 'Fase: ' . $lead->fase_funil;
        if ($lead->tipo_projeto) $partes[] = 'Projeto: ' . $lead->tipo_projeto;
        if ($lead->tipo_imovel) $partes[] = 'Imóvel: ' . $lead->tipo_imovel;
        if ($lead->bairro) $partes[] = 'Bairro: ' . $lead->bairro;
        if ($lead->largura && $lead->comprimento) $partes[] = 'Medidas: ' . $lead->largura . 'x' . $lead->comprimento;
        if ($lead->estrutura_existente) $partes[] = 'Estrutura existente: ' . $lead->estrutura_existente;
        if ($lead->urgencia_real) $partes[] = 'Urgência real: ' . $lead->urgencia_real;
        if ($lead->objecao_principal) $partes[] = 'Objeção: ' . $lead->objecao_principal;
        if ($lead->preferencia_estetica) $partes[] = 'Preferência estética: ' . $lead->preferencia_estetica;
        if ($lead->medo_principal) $partes[] = 'Medo principal: ' . $lead->medo_principal;
        if ($lead->motivo_compra) $partes[] = 'Motivo compra: ' . $lead->motivo_compra;
        if ($lead->proxima_acao) $partes[] = 'Próxima ação: ' . $lead->proxima_acao;

        $lead->resumo_contexto = implode(' | ', $partes);
    }
}
