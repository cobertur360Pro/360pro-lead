<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'nome',
        'telefone',
        'cidade',
        'status'
    ];

    public static function statusList()
    {
        return [
            'novo',
            'contato',
            'orçamento',
            'fechado',
            'perdido'
        ];
    }
}
