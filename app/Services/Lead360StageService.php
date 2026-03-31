<?php

namespace App\Services;

use App\Models\Lead;

class Lead360StageService
{
    public function proximaPergunta(Lead $lead): ?string
    {
        if (! $lead->tipo_projeto) {
            return 'Para eu te conduzir certo, me diga primeiro qual solução você procura dentro da nossa linha: cobertura, sacada, fechamento ou box?';
        }

        if (! $lead->tipo_imovel) {
            return 'Essa instalação será em casa, apartamento ou espaço comercial?';
        }

        if (! $lead->interesse) {
            return 'Esse projeto é para qual área exatamente: quintal, corredor, sacada, espaço gourmet, piscina ou outra área?';
        }

        if (! $lead->bairro && ! $lead->cidade) {
            return 'Em qual bairro ou cidade será essa instalação?';
        }

        if (! $lead->largura || ! $lead->comprimento) {
            return 'Você já tem as medidas aproximadas do local? Pode me passar largura x comprimento, mesmo que seja aproximado.';
        }

        if (! $lead->estrutura_existente) {
            return 'Hoje já existe alguma estrutura no local ou será uma estrutura nova?';
        }

        return null;
    }

    public function contextoMinimoFechado(Lead $lead): bool
    {
        return ! is_null($this->proximaPergunta($lead));
    }
}
