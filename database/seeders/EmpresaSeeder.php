<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        Empresa::updateOrCreate(
            ['slug' => 'baumann'],
            [
                'nome' => 'Baumann Envidraçamento',
                'email' => 'contato@baumann.local',
                'telefone' => '(11) 99999-9999',
                'status' => 'ativa',
            ]
        );
    }
}
