<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ModuloSeeder::class,
            EmpresaSeeder::class,
            EmpresaModuloSeeder::class,
            Lead360ParametroSeeder::class,
            Lead360ParametroValorSeeder::class,
        ]);
    }
}
