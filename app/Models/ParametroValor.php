<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParametroValor extends Model
{
    protected $table = 'parametro_valores';

    protected $fillable = [
        'parametro_id',
        'empresa_id',
        'valor',
    ];

    public function parametro(): BelongsTo
    {
        return $this->belongsTo(Parametro::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
