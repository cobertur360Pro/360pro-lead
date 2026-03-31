<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\EmpresaModulo;
use App\Models\Modulo;
use Illuminate\Database\Seeder;

class EmpresaModuloSeeder extends Seeder
{
    public function run(): void
    {
        $empresa = Empresa::query()->where('slug', 'baumann')->first();

        if (! $empresa) {
            return;
        }

        $codigosHabilitados = [
            'LD001',
            'LD002',
            'LD003',
            'LD008',
        ];

        $modulos = Modulo::query()->get();

        foreach ($modulos as $modulo) {
            EmpresaModulo::updateOrCreate(
                [
                    'empresa_id' => $empresa->id,
                    'modulo_id' => $modulo->id,
                ],
                [
                    'habilitado' => in_array($modulo->codigo, $codigosHabilitados, true),
                ]
            );
        }
    }
}
