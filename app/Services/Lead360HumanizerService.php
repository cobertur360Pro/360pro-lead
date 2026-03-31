<?php

namespace App\Services;

class Lead360HumanizerService
{
    public function humanizar(string $resposta): string
    {
        $resposta = trim($resposta);

        if (! str_starts_with(strtolower($resposta), 'oi') &&
            ! str_starts_with(strtolower($resposta), 'olá') &&
            ! str_starts_with(strtolower($resposta), 'perfeito') &&
            ! str_starts_with(strtolower($resposta), 'entendi')
        ) {
            $resposta = 'Perfeito. ' . $resposta;
        }

        return $resposta;
    }
}
