<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'cidade',
        'bairro',
        'tipo_imovel',
        'tipo_projeto',
        'largura',
        'comprimento',
        'estrutura_existente',
        'material_desejado',
        'perfil_cliente',
        'fase_funil',
        'urgencia_real',
        'preferencia_estetica',
        'objecao_principal',
        'medo_principal',
        'motivo_compra',
        'restricao_orcamento',
        'restricao_prazo',
        'cliente_tecnico',
        'cliente_existente',
        'proxima_acao',
        'data_followup',
        'resumo_contexto',
        'status',
        'observacoes',
        'origem',
        'interesse',
        'urgencia',
        'temperatura',
        'score',
    ];

    protected $casts = [
        'cliente_tecnico' => 'boolean',
        'cliente_existente' => 'boolean',
        'data_followup' => 'date',
    ];

    public static function statusList(): array
    {
        return [
            'novo',
            'contato',
            'orcamento',
            'fechado',
            'perdido',
        ];
    }

    public function interactions(): HasMany
    {
        return $this->hasMany(LeadInteraction::class)->latest();
    }

    public function calcularScore(): int
    {
        $score = 0;

        if ($this->telefone) $score += 10;
        if ($this->cidade) $score += 10;
        if ($this->interesse) $score += 20;
        if ($this->urgencia === 'alta') $score += 20;
        if ($this->status === 'orcamento') $score += 20;
        if ($this->tipo_imovel) $score += 10;
        if ($this->tipo_projeto) $score += 10;
        if ($this->largura || $this->comprimento) $score += 10;
        if ($this->estrutura_existente) $score += 10;
        if ($this->cliente_tecnico) $score += 5;
        if ($this->perfil_cliente === 'arquiteto' || $this->perfil_cliente === 'corporativo') $score += 10;

        return min($score, 100);
    }

    public function atualizarQualificacao(): void
    {
        $this->score = $this->calcularScore();

        if ($this->score >= 70) {
            $this->temperatura = 'quente';
        } elseif ($this->score >= 40) {
            $this->temperatura = 'morno';
        } else {
            $this->temperatura = 'frio';
        }

        $this->save();
    }

    public function fatosConfirmados(): array
    {
        return [
            'nome' => $this->nome,
            'cidade' => $this->cidade,
            'bairro' => $this->bairro,
            'tipo_imovel' => $this->tipo_imovel,
            'tipo_projeto' => $this->tipo_projeto,
            'largura' => $this->largura,
            'comprimento' => $this->comprimento,
            'estrutura_existente' => $this->estrutura_existente,
            'material_desejado' => $this->material_desejado,
            'interesse' => $this->interesse,
            'urgencia' => $this->urgencia,
            'temperatura' => $this->temperatura,
            'perfil_cliente' => $this->perfil_cliente,
            'fase_funil' => $this->fase_funil,
            'urgencia_real' => $this->urgencia_real,
            'preferencia_estetica' => $this->preferencia_estetica,
            'objecao_principal' => $this->objecao_principal,
            'medo_principal' => $this->medo_principal,
            'motivo_compra' => $this->motivo_compra,
            'restricao_orcamento' => $this->restricao_orcamento,
            'restricao_prazo' => $this->restricao_prazo,
            'cliente_tecnico' => $this->cliente_tecnico,
            'cliente_existente' => $this->cliente_existente,
            'proxima_acao' => $this->proxima_acao,
            'data_followup' => optional($this->data_followup)->format('Y-m-d'),
            'resumo_contexto' => $this->resumo_contexto,
        ];
    }
}
