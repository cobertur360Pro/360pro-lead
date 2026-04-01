<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class Lead360OrchestratorService
{
    public function __construct(
        protected Lead360ContextBuilderService $contextBuilder,
        protected Lead360CommercialExtractorService $extractor,
        protected Lead360SemanticExtractorService $semanticExtractor,
        protected Lead360ConversationStateService $stateService,
        protected Lead360DecisionService $decisionService,
        protected Lead360ResponseComposerService $responseComposer,
        protected OpenAIService $openAIService,
    ) {
    }

    public function process(Lead $lead, string $mensagem): array
    {
        $contextoBase = $this->contextBuilder->build($lead);

        $extracaoLexica = $this->extractor->extract($mensagem, $contextoBase);
        $extracaoSemantica = $this->semanticExtractor->extract($mensagem, $contextoBase);

        $extracao = $this->mergeExtractions($extracaoLexica, $extracaoSemantica, $contextoBase);

        $contextoAtualizado = $this->mergeContexto($contextoBase, $extracao);

        $estado = $this->stateService->resolve($contextoAtualizado, $extracao);

        $contextoAtualizado['estado_atual'] = $estado['estado_atual'] ?? null;
        $contextoAtualizado['lacuna_atual'] = $estado['lacuna_atual'] ?? null;
        $contextoAtualizado['proxima_acao'] = $estado['proxima_acao'] ?? null;

        $decisao = $this->decisionService->decide($contextoAtualizado, $extracao, $estado);

        $resposta = $this->buildResponse(
            $mensagem,
            $contextoAtualizado,
            $extracao,
            $estado,
            $decisao
        );

        $debug = [
            'mensagem' => $mensagem,
            'extracao_lexica' => $extracaoLexica,
            'extracao_semantica' => $extracaoSemantica,
            'extracao_final' => $extracao,
            'estado' => $estado,
            'decisao' => $decisao,
            'resposta' => $resposta,
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ];

        $this->persistLead(
            $lead,
            $contextoAtualizado,
            $extracao,
            $estado,
            $decisao,
            $resposta,
            $debug
        );

        return [
            'contexto' => $contextoAtualizado,
            'extracao_lexica' => $extracaoLexica,
            'extracao_semantica' => $extracaoSemantica,
            'extracao' => $extracao,
            'estado' => $estado,
            'decisao' => $decisao,
            'resposta' => $resposta,
            'debug' => $debug,
        ];
    }

    protected function buildResponse(
        string $mensagem,
        array $contexto,
        array $extracao,
        array $estado,
        array $decisao
    ): string {
        $acao = $decisao['acao_principal'] ?? 'acolher';

        $usarOpenAi = in_array($acao, [
            'orientar',
            'defender_valor',
            'segurar_preco',
            'segurar_prazo',
        ], true);

        if ($usarOpenAi) {
            $respostaIa = $this->openAIService->responderLeadOrientacao(
                $mensagem,
                array_merge($contexto, [
                    'acao_atual' => $acao,
                    'estado_resumo' => $estado['resumo_estado'] ?? null,
                    'extracao_atual' => $extracao,
                    'decisao_atual' => $decisao,
                ])
            );

            if ($this->isUsefulAiResponse($respostaIa)) {
                return $this->humanize($respostaIa);
            }
        }

        return $this->humanize(
            $this->responseComposer->compose($contexto, $extracao, $estado, $decisao)
        );
    }

    protected function persistLead(
        Lead $lead,
        array $contexto,
        array $extracao,
        array $estado,
        array $decisao,
        string $resposta,
        array $debug = []
    ): void {
        $updates = [];
        $table = $lead->getTable();

        $mapa = [
            'nome' => $contexto['nome'] ?? null,
            'email' => $contexto['email'] ?? null,
            'bairro' => $contexto['bairro'] ?? null,
            'cidade' => $contexto['cidade'] ?? null,
            'tipo_projeto' => $contexto['solucao_principal'] ?? null,
            'tipo_imovel' => $contexto['tipo_imovel'] ?? null,
            'interesse' => $contexto['area_projeto'] ?? null,
            'largura' => $contexto['largura'] ?? null,
            'comprimento' => $contexto['comprimento'] ?? null,
            'principal_desejo' => $contexto['principal_desejo'] ?? null,
            'urgencia_real' => $contexto['urgencia'] ?? null,
            'objecao_principal' => $contexto['objecao_principal'] ?? null,
            'fase_funil' => $contexto['estagio_decisao'] ?? null,
            'proxima_acao' => $decisao['acao_principal'] ?? null,
            'resumo_contexto' => $contexto['resumo_contexto'] ?? null,
        ];

        foreach ($mapa as $coluna => $valor) {
            if ($valor === null || $valor === '') {
                continue;
            }

            if (Schema::hasColumn($table, $coluna)) {
                $updates[$coluna] = $valor;
            }
        }

        if (
            Schema::hasColumn($table, 'prioridade_atual') &&
            ! empty($contexto['prioridade_atual']) &&
            is_array($contexto['prioridade_atual'])
        ) {
            $updates['prioridade_atual'] = json_encode(
                array_values($contexto['prioridade_atual']),
                JSON_UNESCAPED_UNICODE
            );
        }

        if (Schema::hasColumn($table, 'tem_foto') && ! empty($contexto['tem_foto'])) {
            $updates['tem_foto'] = true;
        }

        if (Schema::hasColumn($table, 'tem_video') && ! empty($contexto['tem_video'])) {
            $updates['tem_video'] = true;
        }

        if (Schema::hasColumn($table, 'tem_projeto') && ! empty($contexto['tem_projeto'])) {
            $updates['tem_projeto'] = true;
        }

        if (! empty($updates)) {
            $lead->forceFill($updates);
        }

        if (Schema::hasColumn($table, 'memoria_estruturada')) {
            $lead->forceFill([
                'memoria_estruturada' => json_encode([
                    'contexto' => Arr::except($contexto, ['historico']),
                    'extracao' => $extracao,
                    'estado' => $estado,
                    'decisao' => $decisao,
                    'ultima_resposta' => $resposta,
                    'debug' => $debug,
                    'updated_at' => now()->format('Y-m-d H:i:s'),
                ], JSON_UNESCAPED_UNICODE),
            ]);
        }

        if ($lead->isDirty()) {
            $lead->save();
        }
    }

    protected function mergeContexto(array $contexto, array $extracao): array
    {
        $merged = $contexto;

        foreach ($extracao as $chave => $valor) {
            if ($valor === null) {
                continue;
            }

            if (is_array($valor) && empty($valor)) {
                continue;
            }

            if (is_bool($valor)) {
                if ($valor === true) {
                    $merged[$chave] = true;
                }

                continue;
            }

            $merged[$chave] = $valor;
        }

        return $merged;
    }

    protected function mergeExtractions(array $lexica, array $semantica, array $contextoBase = []): array
    {
        if (empty($semantica)) {
            return $this->postProcessExtraction($lexica, $contextoBase);
        }

        $merged = $lexica;

        $semanticPriorityFields = [
            'tipo_mensagem',
            'responde_lacuna_atual',
            'mudou_assunto',
            'nome',
            'telefone',
            'email',
            'perfil_contato',
            'cep',
            'endereco',
            'bairro',
            'cidade',
            'solucao_principal',
            'solucao_subtipo',
            'fora_escopo',
            'tipo_imovel',
            'area_projeto',
            'contexto_uso',
            'largura',
            'comprimento',
            'area_informada_m2',
            'tem_foto',
            'tem_video',
            'tem_projeto',
            'principal_desejo',
            'prioridade_atual',
            'urgencia',
            'objecao_principal',
            'estagio_decisao',
            'assistencia',
            'problema_relato',
            'confianca_geral',
            'campos_baixa_confianca',
            'lacuna_respondida',
        ];

        foreach ($semanticPriorityFields as $chave) {
            if (! array_key_exists($chave, $semantica)) {
                continue;
            }

            $valor = $semantica[$chave];

            if ($valor === null) {
                continue;
            }

            if (is_array($valor) && empty($valor)) {
                continue;
            }

            if (is_bool($valor)) {
                $merged[$chave] = $valor;
                continue;
            }

            $merged[$chave] = $valor;
        }

        if (empty($merged['trouxe_dado_novo'])) {
            $merged['trouxe_dado_novo'] = ! empty($lexica['trouxe_dado_novo']) || ! empty($semantica['trouxe_dado_novo']);
        }

        return $this->postProcessExtraction($merged, $contextoBase);
    }

    protected function postProcessExtraction(array $extracao, array $contextoBase = []): array
    {
        $lacunaAtual = $contextoBase['lacuna_atual'] ?? null;

        if (
            $lacunaAtual === 'principal_desejo' &&
            empty($extracao['principal_desejo']) &&
            ! empty($extracao['contexto_uso'])
        ) {
            $extracao['principal_desejo'] = $extracao['contexto_uso'];
            $extracao['responde_lacuna_atual'] = true;
        }

        if (
            $lacunaAtual === 'area_projeto' &&
            ! empty($extracao['area_projeto'])
        ) {
            $extracao['responde_lacuna_atual'] = true;
        }

        if (
            $lacunaAtual === 'tipo_imovel' &&
            ! empty($extracao['tipo_imovel'])
        ) {
            $extracao['responde_lacuna_atual'] = true;
        }

        if (
            $lacunaAtual === 'medida_ou_midia' &&
            (
                (! empty($extracao['largura']) && ! empty($extracao['comprimento']))
                || ! empty($extracao['tem_foto'])
                || ! empty($extracao['tem_video'])
                || ! empty($extracao['tem_projeto'])
            )
        ) {
            $extracao['responde_lacuna_atual'] = true;
        }

        return $extracao;
    }

    protected function isUsefulAiResponse(?string $resposta): bool
    {
        if (! is_string($resposta)) {
            return false;
        }

        $resposta = trim($resposta);

        if ($resposta === '') {
            return false;
        }

        if (str_starts_with($resposta, 'Erro OpenAI:')) {
            return false;
        }

        if (str_starts_with($resposta, 'Erro interno ao consultar a OpenAI:')) {
            return false;
        }

        if ($resposta === 'Sem resposta.') {
            return false;
        }

        return true;
    }

    protected function humanize(string $resposta): string
    {
        if (class_exists(\App\Services\Lead360HumanizerService::class)) {
            try {
                return app(\App\Services\Lead360HumanizerService::class)->humanizar($resposta);
            } catch (\Throwable $e) {
                return trim($resposta);
            }
        }

        return trim($resposta);
    }
}
