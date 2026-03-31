<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Parametro extends Model
{
    protected $table = 'parametros';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'bloco',
        'camada',
        'escopo',
        'tipo',
        'valor_padrao',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function valores(): HasMany
    {
        return $this->hasMany(ParametroValor::class);
    }
}
