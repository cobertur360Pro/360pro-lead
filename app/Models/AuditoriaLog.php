<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaLog extends Model
{
    public $timestamps = false;

    protected $table = 'auditoria_logs';

    protected $fillable = [
        'empresa_id',
        'usuario_id',
        'acao',
        'entidade',
        'entidade_id',
        'dados_antes',
        'dados_depois',
        'created_at',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
