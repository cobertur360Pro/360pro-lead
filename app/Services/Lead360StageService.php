<?php

namespace App\Services;

use App\Models\Lead;

class Lead360StageService
{
   public function proximaPergunta(Lead $lead): ?string
    {
        if (! $lead->tipo_projeto) {
            return 'Me conta uma coisa pra eu te orientar certo 🙂 você está buscando cobertura, fechamento, sacada ou algo dentro dessa linha?';
        }
    
        if (! $lead->tipo_imovel) {
            return 'Perfeito 👍 essa instalação será em casa, apartamento ou espaço comercial?';
        }
    
        if (! $lead->interesse) {
            return 'Entendi. E essa cobertura é pra qual área exatamente? Quintal, corredor, espaço gourmet, piscina…?';
        }
    
        if (! $lead->bairro && ! $lead->cidade) {
            return 'Legal. Em qual bairro ou cidade será essa instalação?';
        }
    
        if (! $lead->largura || ! $lead->comprimento) {
            return 'Você já tem uma noção das medidas? Pode ser aproximado, tipo largura x comprimento.';
        }
    
        if (! $lead->estrutura_existente) {
            return 'Hoje já existe alguma estrutura no local ou vamos partir de algo totalmente novo?';
        }
    
        return null;
    }

   public function contextoMinimoFechado(Lead $lead): bool
    {
        return is_null($this->proximaPergunta($lead));
    }
}
