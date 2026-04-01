<?php

namespace App\Services;

class Lead360ResponseComposerService
{
    public function compose(array $contexto, array $extracao, array $estado, array $decisao): string
    {
        $merged = $this->mergeContexto($contexto, $extracao);
        $acao = $decisao['acao_principal'] ?? 'acolher';
        $lacuna = $estado['lacuna_atual'] ?? null;

        return match ($acao) {
            'bloquear_fora_escopo' => $this->composeForaEscopo(),
            'mudar_para_assistencia' => $this->composeAssistencia($extracao),
            'acolher' => $this->composeAcolhimento($merged),
            'segurar_preco' => $this->composeSegurarPreco($merged),
            'segurar_prazo' => $this->composeSegurarPrazo($merged),
            'encaminhar_visita' => $this->composeEncaminharVisita($merged),
            'defender_valor' => $this->composeDefenderValor($merged, $extracao),
            'encaminhar_humano' => $this->composeEncaminharHumano($merged),
            'orientar' => $this->composeOrientar($merged, $extracao),
            'pedir_material_apoio' => $this->composePedirMaterialApoio($merged, $extracao),
            'perguntar' => $this->composePerguntaPorLacuna($merged, $extracao, $lacuna),
            default => $this->composeAcolhimento($merged),
        };
    }

    protected function composeAcolhimento(array $merged): string
    {
        if (! empty($merged['solucao_principal'])) {
            return 'Perfeito. Vou te ajudar por aqui da forma mais organizada possível. Antes de seguir, me diz seu nome para eu registrar seu atendimento certinho.';
        }

        return 'Oi! Tudo bem 🙂 Sou o assistente da Baumann e vou te ajudar por aqui. Me conta: você está buscando cobertura, fechamento, sacada ou outra solução da nossa linha?';
    }

    protected function composePerguntaPorLacuna(array $merged, array $extracao, ?string $lacuna): string
    {
        return match ($lacuna) {
            'nome' => $this->askNome($merged),
            'localizacao' => $this->askLocalizacao($merged),
            'solucao_principal' => $this->askSolucao($merged),
            'tipo_imovel' => $this->askTipoImovel($merged, $extracao),
            'area_projeto' => $this->askAreaProjeto($merged, $extracao),
            'medida_ou_midia' => $this->askMedidaOuMidia($merged, $extracao),
            'principal_desejo' => $this->askPrincipalDesejo($merged, $extracao),
            'prioridade_atual' => $this->askPrioridadeAtual($merged),
            default => $this->composeAcolhimento($merged),
        };
    }

    protected function composePedirMaterialApoio(array $merged, array $extracao): string
    {
        $abertura = $this->buildContextReflection($extracao);

        if ($abertura === null && ! empty($merged['area_projeto'])) {
            $abertura = 'Perfeito. Então é para ' . $this->formatArea($merged['area_projeto']) . '.';
        }

        return trim(
            ($abertura ? $abertura . ' ' : '')
            . 'Se você já tiver a medida aproximada ou alguma foto/vídeo do local, isso já ajuda bastante a te orientar certo.'
        );
    }

    protected function composeSegurarPreco(array $merged): string
    {
        return 'Consigo te orientar em relação a valor, sim. Só preciso de uma base mínima para não te passar algo impreciso. Me ajuda com seu nome, o CEP do local e uma medida aproximada ou foto/vídeo da área?';
    }

    protected function composeSegurarPrazo(array $merged): string
    {
        return 'Entendi a urgência. Prazo depende do tipo de projeto e da política vigente da empresa, então eu prefiro te orientar com base certa. Me passa seu nome e o contexto do local para eu organizar isso melhor.';
    }

    protected function composeEncaminharVisita(array $merged): string
    {
        return 'Perfeito. Nesse caso, faz sentido mesmo uma avaliação mais precisa no local. Vou deixar isso encaminhado para verificarmos a melhor disponibilidade com a equipe responsável.';
    }

    protected function composeDefenderValor(array $merged, array $extracao): string
    {
        return 'Entendo sua preocupação com o valor. Nesse tipo de projeto, a comparação correta precisa olhar estrutura, vidro, acabamento, vedação e execução. Se fizer sentido, eu posso te conduzir por um caminho mais coerente com o que você espera, sem te empurrar uma solução errada.';
    }

    protected function composeEncaminharHumano(array $merged): string
    {
        return 'Perfeito. Com o que você me passou, já dá para avançar bem. Vou deixar isso encaminhado para um especialista da equipe seguir com você da forma mais precisa.';
    }

    protected function composeOrientar(array $merged, array $extracao): string
    {
        if (! empty($merged['objecao_principal']) && $merged['objecao_principal'] === 'preco') {
            return $this->composeDefenderValor($merged, $extracao);
        }

        if (! empty($merged['principal_desejo'])) {
            return 'Entendi o cenário. Pelo que você me passou, o melhor agora é te orientar com base no que faz mais sentido para o seu projeto, equilibrando solução, acabamento e resultado final. Se quiser, eu sigo organizando isso para o próximo passo.';
        }

        return 'Entendi o cenário. Aqui faz mais sentido eu te orientar com critério do que te empurrar uma resposta pronta. Me deixa organizar o próximo passo da forma mais coerente para o seu projeto.';
    }

    protected function composeForaEscopo(): string
    {
        return 'Hoje nosso atendimento está focado nas soluções da linha Baumann, como coberturas em vidro, policarbonato e envidraçamentos. Se o seu projeto estiver nessa linha, eu sigo com você por aqui.';
    }

    protected function composeAssistencia(array $extracao): string
    {
        $problema = $extracao['problema_relato'] ?? null;

        if ($problema === 'infiltracao') {
            return 'Entendi. Vamos tratar isso como assistência. Me confirma por favor onde está entrando água e, se puder, me envie foto ou vídeo para eu deixar o atendimento bem direcionado.';
        }

        if ($problema === 'porta travando') {
            return 'Entendi. Vamos tratar isso como assistência. Me confirma por favor como está ocorrendo o travamento da porta e, se puder, me envie foto ou vídeo para eu encaminhar corretamente.';
        }

        if ($problema === 'pendencia de obra') {
            return 'Entendi. Vou tratar isso como acompanhamento de obra. Se puder, me confirma o que ficou pendente e me envie foto ou vídeo para eu direcionar isso da forma mais organizada.';
        }

        return 'Entendi. Vamos tratar isso como assistência. Me confirma por favor o problema exato e, se puder, envie foto ou vídeo para eu deixar o atendimento bem direcionado.';
    }

    protected function askNome(array $merged): string
    {
        if (! empty($merged['solucao_principal'])) {
            return 'Perfeito. Antes de seguir com seu atendimento, me diz seu nome para eu registrar tudo certinho.';
        }

        return 'Perfeito. Antes de seguir, me diz seu nome para eu registrar seu atendimento certinho.';
    }

    protected function askLocalizacao(array $merged): string
    {
        return 'Ótimo. Pra eu te orientar com mais precisão, me passa o CEP do local da instalação. Se preferir, pode me informar pelo menos o bairro e a cidade.';
    }

    protected function askSolucao(array $merged): string
    {
        return 'Me conta uma coisa pra eu te orientar certo: você está buscando cobertura, fechamento, sacada ou outra solução dentro da nossa linha?';
    }

    protected function askTipoImovel(array $merged, array $extracao): string
    {
        $reflexo = $this->buildContextReflection($extracao);

        if ($reflexo) {
            return $reflexo . ' E essa instalação será em casa, apartamento ou espaço comercial?';
        }

        if (! empty($merged['solucao_principal'])) {
            return 'Perfeito. E essa ' . $merged['solucao_principal'] . ' será em casa, apartamento ou espaço comercial?';
        }

        return 'Perfeito. Essa instalação será em casa, apartamento ou espaço comercial?';
    }

    protected function askAreaProjeto(array $merged, array $extracao): string
    {
        $reflexo = $this->buildContextReflection($extracao);

        if ($reflexo) {
            return $reflexo . ' E essa instalação é para qual área exatamente? Garagem, quintal, corredor, espaço gourmet, fundos, varanda...?';
        }

        if (! empty($merged['solucao_principal']) && ! empty($merged['tipo_imovel'])) {
            return 'Entendi. E essa ' . $merged['solucao_principal'] . ' é para qual área da sua ' . $merged['tipo_imovel'] . ' exatamente? Garagem, quintal, corredor, espaço gourmet, fundos, varanda...?';
        }

        return 'Entendi. E essa instalação é para qual área exatamente? Garagem, quintal, corredor, espaço gourmet, fundos, varanda...?';
    }

    protected function askMedidaOuMidia(array $merged, array $extracao): string
    {
        $reflexo = $this->buildContextReflection($extracao);

        if ($reflexo) {
            return $reflexo . ' Se você já tiver a medida aproximada ou alguma foto/vídeo do local, isso já ajuda bastante.';
        }

        if (! empty($merged['area_projeto'])) {
            return 'Perfeito. Então é para ' . $this->formatArea($merged['area_projeto']) . '. Se você já tiver a medida aproximada ou alguma foto/vídeo do local, isso já ajuda bastante.';
        }

        return 'Se você já tiver a medida aproximada ou alguma foto/vídeo do local, isso já ajuda bastante a te orientar certo.';
    }

    protected function askPrincipalDesejo(array $merged, array $extracao): string
    {
        $reflexo = $this->buildContextReflection($extracao);

        if ($reflexo) {
            return $reflexo . ' Agora me ajuda com uma parte importante: o que você mais busca com esse projeto? Proteção, conforto, estética, segurança ou uso do espaço?';
        }

        return 'Agora me ajuda com uma parte importante: o que você mais busca com esse projeto? Proteção, conforto, estética, segurança ou uso do espaço?';
    }

    protected function askPrioridadeAtual(array $merged): string
    {
        return 'Entendi. E olhando para esse projeto agora, o que pesa mais na sua decisão: prazo, qualidade, preço, forma de pagamento ou outro ponto?';
    }

    protected function buildContextReflection(array $extracao): ?string
    {
        if (! empty($extracao['nome'])) {
            return 'Perfeito, ' . $extracao['nome'] . '.';
        }

        if (! empty($extracao['largura']) && ! empty($extracao['comprimento'])) {
            return 'Perfeito, já anotei a medida aproximada de ' . $extracao['largura'] . ' x ' . $extracao['comprimento'] . '.';
        }

        if (! empty($extracao['area_projeto']) && ! empty($extracao['tipo_imovel']) && ! empty($extracao['solucao_principal'])) {
            return 'Perfeito. Então é uma ' . $extracao['solucao_principal'] . ' para ' . $this->formatArea($extracao['area_projeto']) . ' da sua ' . $extracao['tipo_imovel'] . '.';
        }

        if (! empty($extracao['area_projeto'])) {
            return 'Perfeito. Então é para ' . $this->formatArea($extracao['area_projeto']) . '.';
        }

        if (! empty($extracao['tipo_imovel'])) {
            return 'Entendi, será em ' . $this->withArticle($extracao['tipo_imovel']) . '.';
        }

        if (! empty($extracao['solucao_principal'])) {
            return 'Perfeito, já entendi que você busca ' . $this->withArticle($extracao['solucao_principal']) . '.';
        }

        if (! empty($extracao['principal_desejo'])) {
            return 'Entendi, então o foco maior está em ' . $this->humanizeDesejo($extracao['principal_desejo']) . '.';
        }

        if (! empty($extracao['prioridade_atual'])) {
            return 'Perfeito, já entendi melhor o que pesa mais na sua decisão.';
        }

        return null;
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

    protected function formatArea(string $area): string
    {
        return match ($area) {
            'espaco gourmet' => 'o espaço gourmet',
            'area externa' => 'a área externa',
            'estudio fotografico' => 'o estúdio fotográfico',
            default => (str_starts_with($area, 'a ') || str_starts_with($area, 'o ')) ? $area : 'a ' . $area,
        };
    }

    protected function withArticle(string $termo): string
    {
        return match ($termo) {
            'casa' => 'uma casa',
            'apartamento' => 'um apartamento',
            'comercial' => 'um espaço comercial',
            'cobertura' => 'uma cobertura',
            'fechamento' => 'um fechamento',
            'sacada' => 'uma sacada',
            default => $termo,
        };
    }

    protected function humanizeDesejo(string $desejo): string
    {
        return match ($desejo) {
            'protecao chuva' => 'proteção contra chuva',
            'conforto termico' => 'conforto térmico',
            'uso do espaco' => 'uso do espaço',
            'seguranca' => 'segurança',
            'estetica' => 'estética',
            default => $desejo,
        };
    }
}
