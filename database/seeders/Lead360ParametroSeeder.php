<?php

namespace Database\Seeders;

use App\Models\Parametro;
use Illuminate\Database\Seeder;

class Lead360ParametroSeeder extends Seeder
{
    public function run(): void
    {
        $parametros = [
            ['codigo' => 'LDA-001', 'nome' => 'Atendimento IA habilitado', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-002', 'nome' => 'Qualificação automática habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-003', 'nome' => 'Memória estruturada habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-004', 'nome' => 'Pré-orçamento habilitado', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-005', 'nome' => 'Pós-venda habilitado', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-006', 'nome' => 'Assistência habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],

            ['codigo' => 'LDA-010', 'nome' => 'Integração OpenAI habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-011', 'nome' => 'Integração WhatsApp habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-012', 'nome' => 'Integração Kommo habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-013', 'nome' => 'Integração Cobertura360 habilitada', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],

            ['codigo' => 'LDA-020', 'nome' => 'IA pode agendar diretamente', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-021', 'nome' => 'IA pode gerar orçamento diretamente', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-022', 'nome' => 'IA pode falar preço sem contexto', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-023', 'nome' => 'IA pode encaminhar para humano', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-024', 'nome' => 'IA exige fotos para orçamento', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-025', 'nome' => 'IA exige medidas para orçamento', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],

            ['codigo' => 'LDA-030', 'nome' => 'Modo simples ativo', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-031', 'nome' => 'Modo avançado ativo', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-032', 'nome' => 'Modo especialista ativo', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '0'],
            ['codigo' => 'LDA-033', 'nome' => 'Mostrar campos avançados recolhidos', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
            ['codigo' => 'LDA-034', 'nome' => 'Seguir fluxo guiado por etapas', 'bloco' => 'LDA', 'tipo' => 'bool', 'valor_padrao' => '1'],
        ];

        foreach ($parametros as $item) {
            Parametro::updateOrCreate(
                ['codigo' => $item['codigo']],
                [
                    'nome' => $item['nome'],
                    'descricao' => null,
                    'bloco' => $item['bloco'],
                    'camada' => 'core',
                    'escopo' => 'empresa',
                    'tipo' => $item['tipo'],
                    'valor_padrao' => $item['valor_padrao'],
                    'ativo' => true,
                ]
            );
        }
    }
}
