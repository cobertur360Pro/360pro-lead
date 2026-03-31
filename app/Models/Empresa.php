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

    public function empresaModulos(): HasMany
    {
        return $this->hasMany(EmpresaModulo::class);
    }

    public function parametroValores(): HasMany
    {
        return $this->hasMany(ParametroValor::class);
    }

    public function auditoriaLogs(): HasMany
    {
        return $this->hasMany(AuditoriaLog::class);
    }
}
