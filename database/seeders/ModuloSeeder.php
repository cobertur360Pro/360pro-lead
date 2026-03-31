<?php

namespace Database\Seeders;

use App\Models\Modulo;
use Illuminate\Database\Seeder;

class ModuloSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [
            [
                'codigo' => 'LD001',
                'nome' => 'Atendimento IA',
                'descricao' => 'Atendimento automático comercial',
                'camada' => 'core',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD002',
                'nome' => 'Qualificação de Leads',
                'descricao' => 'Classificação automática de lead',
                'camada' => 'core',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD003',
                'nome' => 'Memória Estruturada',
                'descricao' => 'Memória comercial e técnica do lead',
                'camada' => 'core',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD004',
                'nome' => 'Agenda Comercial',
                'descricao' => 'Sugestão e organização de próximos passos',
                'camada' => 'avancado',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD005',
                'nome' => 'Pré-Orçamento',
                'descricao' => 'Coleta orientada para orçamento',
                'camada' => 'avancado',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD006',
                'nome' => 'Assistente de Orçamento',
                'descricao' => 'Integração com lógica de orçamento',
                'camada' => 'especialista',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD007',
                'nome' => 'Integração WhatsApp',
                'descricao' => 'Canal de entrada e saída',
                'camada' => 'avancado',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD008',
                'nome' => 'Integração OpenAI',
                'descricao' => 'Motor conversacional',
                'camada' => 'core',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD009',
                'nome' => 'Integração Kommo',
                'descricao' => 'Integração com CRM externo',
                'camada' => 'avancado',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD010',
                'nome' => 'Integração Cobertura360',
                'descricao' => 'Integração com ERP técnico',
                'camada' => 'especialista',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD011',
                'nome' => 'Pós-venda',
                'descricao' => 'Fluxos de acompanhamento após fechamento',
                'camada' => 'avancado',
                'ativo' => true,
            ],
            [
                'codigo' => 'LD012',
                'nome' => 'Assistência Técnica',
                'descricao' => 'Fluxos de triagem de suporte',
                'camada' => 'avancado',
                'ativo' => true,
            ],
        ];

        foreach ($modulos as $modulo) {
            Modulo::updateOrCreate(
                ['codigo' => $modulo['codigo']],
                $modulo
            );
        }
    }
}
