<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    protected $table = 'empresas';

    protected $fillable = [
        'nome',
        'slug',
        'email',
        'telefone',
        'status',
    ];

    public function modulos(): HasMany
    {
        return $this->hasMany(EmpresaModulo::class);
    }

    public function parametros(): HasMany
    {
        return $this->hasMany(ParametroValor::class);
    }

    public function auditorias(): HasMany
    {
        return $this->hasMany(AuditoriaLog::class);
    }
}
