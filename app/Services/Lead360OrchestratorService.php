<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\Schema;

class Lead360OrchestratorService
{
    public function __construct(
        protected Lead360StructuredTurnService $turnService
    ) {
    }

    public function process(Lead $lead, string $mensagem): array
    {
        $contexto = [
            'nome' => $lead->nome,
            'cidade' => $lead->cidade,
            'bairro' => $lead->bairro,
            'solucao_principal' => $lead->tipo_projeto,
            'area_projeto' => $lead->interesse,
        ];

        $turno = $this->turnService->process($mensagem, $contexto);

        $extracted = $turno['extracted'] ?? [];

        // Atualiza lead
        $updates = [];

        if (!empty($extracted['nome'])) {
            $updates['nome'] = $extracted['nome'];
        }

        if (!empty($extracted['solucao_principal'])) {
            $updates['tipo_projeto'] = $extracted['solucao_principal'];
        }

        if (!empty($extracted['area_projeto'])) {
            $updates['interesse'] = $extracted['area_projeto'];
        }

        if (!empty($extracted['largura'])) {
            $updates['largura'] = $extracted['largura'];
        }

        if (!empty($extracted['comprimento'])) {
            $updates['comprimento'] = $extracted['comprimento'];
        }

        if (!empty($updates)) {
            $lead->update($updates);
        }

        // salva debug
        if (Schema::hasColumn($lead->getTable(), 'memoria_estruturada')) {
            $lead->update([
                'memoria_estruturada' => json_encode($turno, JSON_UNESCAPED_UNICODE)
            ]);
        }

        return [
            'resposta' => $turno['reply'] ?? 'Sem resposta.',
            'debug' => $turno
        ];
    }
}
