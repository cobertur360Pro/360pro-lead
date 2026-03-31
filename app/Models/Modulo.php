<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Modulo extends Model
{
    protected $table = 'modulos';

    protected $fillable = [
        'codigo',
        'nome',
        'descricao',
        'camada',
        'ativo',
    ];

    protected $casts = [
        'ativo' => 'boolean',
    ];

    public function empresaModulos(): HasMany
    {
        return $this->hasMany(EmpresaModulo::class);
    }
}
