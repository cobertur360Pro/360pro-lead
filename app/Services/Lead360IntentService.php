<?php

namespace App\Services;

class Lead360IntentService
{
    public function detectar(string $mensagem): string
    {
        $msg = mb_strtolower(trim($mensagem));

        // Saudação
        if (
            str_contains($msg, 'oi') ||
            str_contains($msg, 'olá') ||
            str_contains($msg, 'boa tarde') ||
            str_contains($msg, 'bom dia') ||
            str_contains($msg, 'boa noite') ||
            str_contains($msg, 'tudo bem')
        ) {
            return 'saudacao';
        }

        // Dúvida sobre quem está falando
        if (
            str_contains($msg, 'você é') ||
            str_contains($msg, 'vc é') ||
            str_contains($msg, 'é uma pessoa') ||
            str_contains($msg, 'é robô') ||
            str_contains($msg, 'é humano')
        ) {
            return 'identidade';
        }

        // Não entendeu
        if (
            str_contains($msg, 'não entendi') ||
            str_contains($msg, 'nao entendi') ||
            str_contains($msg, 'como assim') ||
            str_contains($msg, 'que isso') ||
            str_contains($msg, 'hã') ||
            str_contains($msg, '?')
        ) {
            return 'duvida';
        }

        return 'normal';
    }
}
