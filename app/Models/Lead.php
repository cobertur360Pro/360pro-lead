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
        'status',
        'observacoes',
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
}
