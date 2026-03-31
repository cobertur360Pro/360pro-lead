<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Parametro;
use App\Models\ParametroValor;
use Illuminate\Database\Seeder;

class Lead360ParametroValorSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'baumann')->first();

        if (! $empresa) {
            return;
        }

        $valores = [
            'LDA-001' => '1',
            'LDA-002' => '1',
            'LDA-003' => '1',
            'LDA-004' => '0',
            'LDA-005' => '0',
            'LDA-006' => '0',

            'LDA-010' => '1',
            'LDA-011' => '0',
            'LDA-012' => '0',
            'LDA-013' => '0',

            'LDA-020' => '0',
            'LDA-021' => '0',
            'LDA-022' => '0',
            'LDA-023' => '1',
            'LDA-024' => '1',
            'LDA-025' => '1',

            'LDA-030' => '1',
            'LDA-031' => '1',
            'LDA-032' => '0',
            'LDA-033' => '1',
            'LDA-034' => '1',
        ];

        foreach ($valores as $codigo => $valor) {
            $parametro = Parametro::query()->where('codigo', $codigo)->first();

            if (! $parametro) {
                continue;
            }

            ParametroValor::updateOrCreate(
                [
                    'parametro_id' => $parametro->id,
                    'empresa_id' => $empresa->id,
                ],
                [
                    'valor' => $valor,
                ]
            );
        }
    }
}
