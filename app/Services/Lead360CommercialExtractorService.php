<?php

namespace App\Services;

use Illuminate\Support\Str;

class Lead360CommercialExtractorService
{
    public function extract(string $mensagem, array $contexto = []): array
    {
        $textoOriginal = trim($mensagem);
        $texto = $this->normalize($textoOriginal);

        $resultado = [
            'tipo_mensagem' => $this->detectMessageType($texto),
            'responde_lacuna_atual' => false,
            'trouxe_dado_novo' => false,
            'mudou_assunto' => false,

            'nome' => $this->extractNome($textoOriginal, $texto),
            'telefone' => $this->extractTelefone($textoOriginal),
            'email' => $this->extractEmail($textoOriginal),
            'perfil_contato' => $this->extractPerfilContato($texto),

            'cep' => $this->extractCep($textoOriginal),
            'endereco' => null,
            'bairro' => $this->extractBairro($texto),
            'cidade' => $this->extractCidade($texto),

            'solucao_principal' => $this->extractSolucaoPrincipal($texto),
            'solucao_subtipo' => $this->extractSolucaoSubtipo($texto),
            'fora_escopo' => $this->detectForaEscopo($texto),

            'tipo_imovel' => $this->extractTipoImovel($texto),
            'area_projeto' => $this->extractAreaProjeto($texto),
            'contexto_uso' => $this->extractContextoUso($texto),

            'largura' => null,
            'comprimento' => null,
            'area_informada_m2' => $this->extractAreaM2($texto),
            'tem_foto' => $this->detectFoto($texto),
            'tem_video' => $this->detectVideo($texto),
            'tem_projeto' => $this->detectProjeto($texto),

            'principal_desejo' => $this->extractPrincipalDesejo($texto),
            'prioridade_atual' => $this->extractPrioridades($texto),
            'urgencia' => $this->extractUrgencia($texto),
            'objecao_principal' => $this->extractObjecao($texto),
            'estagio_decisao' => $this->extractEstagioDecisao($texto),

            'assistencia' => $this->detectAssistencia($texto),
            'problema_relato' => $this->extractProblemaRelato($texto),

            'confianca_geral' => 'media',
            'campos_baixa_confianca' => [],
        ];

        [$largura, $comprimento] = $this->extractMedidas($textoOriginal, $texto);
        $resultado['largura'] = $largura;
        $resultado['comprimento'] = $comprimento;

        $resultado['responde_lacuna_atual'] = $this->respondeLacunaAtual(
            $contexto['lacuna_atual'] ?? null,
            $resultado
        );

        $resultado['trouxe_dado_novo'] = $this->trouxeDadoNovo($resultado);
        $resultado['mudou_assunto'] = $this->mudouAssunto($resultado, $contexto);

        $this->ajustarConfianca($resultado, $textoOriginal, $texto);

        return $resultado;
    }

    protected function detectMessageType(string $texto): string
    {
        if ($this->detectForaEscopo($texto)) {
            return 'fora_escopo';
        }

        if ($this->detectAssistencia($texto)) {
            return 'assistencia';
        }

        if ($this->isGreeting($texto)) {
            return 'saudacao';
        }

        if ($this->isUrgencia($texto)) {
            return 'urgencia';
        }

        if ($this->isPedidoPreco($texto)) {
            return 'pedido_preco';
        }

        if ($this->isPedidoPrazo($texto)) {
            return 'pedido_prazo';
        }

        if ($this->isPedidoVisita($texto)) {
            return 'pedido_visita';
        }

        if ($this->isObjecao($texto)) {
            return 'objecao';
        }

        if ($this->isFormalizacao($texto)) {
            return 'formalizacao';
        }

        if (mb_strlen($texto) <= 18) {
            return 'resposta_curta';
        }

        return 'resposta_objetiva';
    }

    protected function extractNome(string $textoOriginal, string $texto): ?string
    {
        $padroes = [
            '/meu nome e\s+([a-zA-ZÀ-ÿ\s]{2,60})/iu',
            '/me chamo\s+([a-zA-ZÀ-ÿ\s]{2,60})/iu',
            '/sou o\s+([a-zA-ZÀ-ÿ\s]{2,60})/iu',
            '/sou a\s+([a-zA-ZÀ-ÿ\s]{2,60})/iu',
        ];

        foreach ($padroes as $padrao) {
            if (preg_match($padrao, $textoOriginal, $matches)) {
                $nome = trim($matches[1]);

                if ($this->isNomeProvavel($nome)) {
                    return $this->sanitizeNome($nome);
                }
            }
        }

        if (
            mb_strlen(trim($textoOriginal)) >= 2 &&
            mb_strlen(trim($textoOriginal)) <= 40 &&
            ! str_contains($texto, ' ') &&
            preg_match('/^[\p{L}]+$/u', trim($textoOriginal))
        ) {
            return $this->sanitizeNome($textoOriginal);
        }

        return null;
    }

    protected function extractTelefone(string $textoOriginal): ?string
    {
        if (preg_match('/(\+?\d[\d\-\s\(\)]{7,}\d)/', $textoOriginal, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

    protected function extractEmail(string $textoOriginal): ?string
    {
        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/iu', $textoOriginal, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }

    protected function extractCep(string $textoOriginal): ?string
    {
        if (preg_match('/\b\d{5}\-?\d{3}\b/', $textoOriginal, $matches)) {
            return trim($matches[0]);
        }

        return null;
    }

    protected function extractPerfilContato(string $texto): ?string
    {
        if (str_contains($texto, 'arquiteta') || str_contains($texto, 'arquiteto')) {
            return 'arquiteto';
        }

        if (str_contains($texto, 'engenheira') || str_contains($texto, 'engenheiro')) {
            return 'engenheiro';
        }

        if (str_contains($texto, 'sindico') || str_contains($texto, 'sindica')) {
            return 'sindico';
        }

        if (str_contains($texto, 'empresa') || str_contains($texto, 'comercial')) {
            return 'comercial';
        }

        return null;
    }

    protected function extractBairro(string $texto): ?string
    {
        $bairros = [
            'ipiranga',
            'mooca',
            'tatuape',
            'tatuapé',
            'vila prudente',
            'cambuci',
            'saude',
            'saúde',
            'brooklin',
            'itaim bibi',
            'vila ipojuca',
            'jardim da saude',
            'jardim da saúde',
        ];

        foreach ($bairros as $bairro) {
            if (str_contains($texto, $this->normalize($bairro))) {
                return $bairro;
            }
        }

        return null;
    }

    protected function extractCidade(string $texto): ?string
    {
        $cidades = [
            'sao paulo' => 'São Paulo',
            'santo andre' => 'Santo André',
            'sao bernardo' => 'São Bernardo',
            'sao caetano' => 'São Caetano',
            'campinas' => 'Campinas',
        ];

        foreach ($cidades as $busca => $cidade) {
            if (str_contains($texto, $busca)) {
                return $cidade;
            }
        }

        return null;
    }

    protected function extractSolucaoPrincipal(string $texto): ?string
    {
        if (str_contains($texto, 'cobertura')) {
            return 'cobertura';
        }

        if (str_contains($texto, 'fechamento')) {
            return 'fechamento';
        }

        if (str_contains($texto, 'sacada')) {
            return 'sacada';
        }

        if (str_contains($texto, 'box')) {
            return 'box';
        }

        return null;
    }

    protected function extractSolucaoSubtipo(string $texto): ?string
    {
        if (str_contains($texto, 'retratil') || str_contains($texto, 'retratil')) {
            return 'retratil';
        }

        if (str_contains($texto, 'fixa') || str_contains($texto, 'fixo')) {
            return 'fixa';
        }

        return null;
    }

    protected function detectForaEscopo(string $texto): bool
    {
        $itens = [
            'toldo',
            'telha de barro',
            'madeira',
            'pergolado de madeira',
        ];

        foreach ($itens as $item) {
            if (str_contains($texto, $this->normalize($item))) {
                return true;
            }
        }

        return false;
    }

    protected function extractTipoImovel(string $texto): ?string
    {
        if (str_contains($texto, 'casa')) {
            return 'casa';
        }

        if (str_contains($texto, 'apartamento')) {
            return 'apartamento';
        }

        if (
            str_contains($texto, 'comercial') ||
            str_contains($texto, 'empresa') ||
            str_contains($texto, 'faculdade') ||
            str_contains($texto, 'estudio') ||
            str_contains($texto, 'estudio fotografico') ||
            str_contains($texto, 'estudio fotográfico')
        ) {
            return 'comercial';
        }

        return null;
    }

    protected function extractAreaProjeto(string $texto): ?string
    {
        $mapa = [
            'garagem' => 'garagem',
            'quintal' => 'quintal',
            'corredor' => 'corredor',
            'espaco gourmet' => 'espaco gourmet',
            'espaço gourmet' => 'espaco gourmet',
            'fundos' => 'fundos',
            'sacada' => 'sacada',
            'varanda' => 'varanda',
            'piscina' => 'piscina',
            'area externa' => 'area externa',
            'área externa' => 'area externa',
            'estudio' => 'estudio',
            'estudio fotografico' => 'estudio fotografico',
            'frente' => 'frente',
            'lateral' => 'lateral',
        ];

        foreach ($mapa as $busca => $valor) {
            if (str_contains($texto, $this->normalize($busca))) {
                return $valor;
            }
        }

        return null;
    }

    protected function extractContextoUso(string $texto): ?string
    {
        if (str_contains($texto, 'proteger da chuva') || str_contains($texto, 'chuva')) {
            return 'protecao chuva';
        }

        if (str_contains($texto, 'calor')) {
            return 'conforto termico';
        }

        if (str_contains($texto, 'estetica') || str_contains($texto, 'estética') || str_contains($texto, 'bonito')) {
            return 'estetica';
        }

        if (str_contains($texto, 'uso do espaco') || str_contains($texto, 'uso do espaço')) {
            return 'uso do espaco';
        }

        if (str_contains($texto, 'seguranca') || str_contains($texto, 'segurança')) {
            return 'seguranca';
        }

        return null;
    }

    protected function extractMedidas(string $textoOriginal, string $texto): array
    {
        if (preg_match('/(\d+[.,]?\d*)\s*[xX]\s*(\d+[.,]?\d*)/', $textoOriginal, $matches)) {
            return [$this->normalizeNumber($matches[1]), $this->normalizeNumber($matches[2])];
        }

        if (preg_match('/(\d+[.,]?\d*)\s*por\s*(\d+[.,]?\d*)/i', $texto, $matches)) {
            return [$this->normalizeNumber($matches[1]), $this->normalizeNumber($matches[2])];
        }

        return [null, null];
    }

    protected function extractAreaM2(string $texto): ?string
    {
        if (preg_match('/(\d+[.,]?\d*)\s*m2/', $texto, $matches)) {
            return $this->normalizeNumber($matches[1]);
        }

        return null;
    }

    protected function detectFoto(string $texto): bool
    {
        return str_contains($texto, 'foto') || str_contains($texto, 'fotos');
    }

    protected function detectVideo(string $texto): bool
    {
        return str_contains($texto, 'video') || str_contains($texto, 'videos') || str_contains($texto, 'vídeo') || str_contains($texto, 'vídeos');
    }

    protected function detectProjeto(string $texto): bool
    {
        return str_contains($texto, 'projeto') || str_contains($texto, 'planta') || str_contains($texto, 'pdf');
    }

    protected function extractPrincipalDesejo(string $texto): ?string
    {
        if (str_contains($texto, 'seguranca') || str_contains($texto, 'segurança')) {
            return 'seguranca';
        }

        if (str_contains($texto, 'proteger da chuva') || str_contains($texto, 'nao molhar') || str_contains($texto, 'não molhar')) {
            return 'protecao chuva';
        }

        if (str_contains($texto, 'conforto') || str_contains($texto, 'calor')) {
            return 'conforto termico';
        }

        if (str_contains($texto, 'estetica') || str_contains($texto, 'estética') || str_contains($texto, 'bonito')) {
            return 'estetica';
        }

        if (str_contains($texto, 'garantir o uso') || str_contains($texto, 'usar o espaco') || str_contains($texto, 'usar o espaço')) {
            return 'uso do espaco';
        }

        return null;
    }

    protected function extractPrioridades(string $texto): array
    {
        $prioridades = [];

        if (str_contains($texto, 'prazo')) {
            $prioridades[] = 'prazo';
        }

        if (str_contains($texto, 'qualidade')) {
            $prioridades[] = 'qualidade';
        }

        if (str_contains($texto, 'preco') || str_contains($texto, 'preço')) {
            $prioridades[] = 'preco';
        }

        if (str_contains($texto, 'pagamento') || str_contains($texto, 'parcelamento') || str_contains($texto, 'parcelar')) {
            $prioridades[] = 'pagamento';
        }

        return array_values(array_unique($prioridades));
    }

    protected function extractUrgencia(string $texto): ?string
    {
        if (
            str_contains($texto, 'urgente') ||
            str_contains($texto, 'preciso rapido') ||
            str_contains($texto, 'preciso rapido') ||
            str_contains($texto, 'preciso fechar rapido') ||
            str_contains($texto, 'obra tem prazo') ||
            str_contains($texto, 'prazo curto')
        ) {
            return 'alta';
        }

        if (str_contains($texto, 'sem pressa') || str_contains($texto, 'sem urgencia') || str_contains($texto, 'sem urgência')) {
            return 'baixa';
        }

        return null;
    }

    protected function extractObjecao(string $texto): ?string
    {
        if (
            str_contains($texto, 'mais em conta') ||
            str_contains($texto, 'ficou caro') ||
            str_contains($texto, 'esta caro') ||
            str_contains($texto, 'está caro') ||
            str_contains($texto, 'acima do que imaginei')
        ) {
            return 'preco';
        }

        if (str_contains($texto, 'prazo')) {
            return 'prazo';
        }

        return null;
    }

    protected function extractEstagioDecisao(string $texto): ?string
    {
        if (
            str_contains($texto, 'fechado') ||
            str_contains($texto, 'quero fechar') ||
            str_contains($texto, 'vamos fechar')
        ) {
            return 'fechamento';
        }

        if (
            str_contains($texto, 'estou cotando') ||
            str_contains($texto, 'adiantando orcamentos') ||
            str_contains($texto, 'adiantando orçamentos') ||
            str_contains($texto, 'levantando orcamento') ||
            str_contains($texto, 'levantando orçamento')
        ) {
            return 'levantando orçamento';
        }

        if (str_contains($texto, 'só pesquisando') || str_contains($texto, 'estudando')) {
            return 'pesquisa';
        }

        return null;
    }

    protected function detectAssistencia(string $texto): bool
    {
        $sinais = [
            'entra agua',
            'entrando agua',
            'porta travando',
            'manutencao',
            'manutenção',
            'assistencia',
            'assistência',
            'servico nao deu certo',
            'serviço não deu certo',
            'vazamento',
            'termino do trabalho',
            'término do trabalho',
        ];

        foreach ($sinais as $sinal) {
            if (str_contains($texto, $this->normalize($sinal))) {
                return true;
            }
        }

        return false;
    }

    protected function extractProblemaRelato(string $texto): ?string
    {
        if (! $this->detectAssistencia($texto)) {
            return null;
        }

        if (str_contains($texto, 'entra agua') || str_contains($texto, 'entrando agua') || str_contains($texto, 'vazamento')) {
            return 'infiltracao';
        }

        if (str_contains($texto, 'porta travando')) {
            return 'porta travando';
        }

        if (str_contains($texto, 'termino do trabalho') || str_contains($texto, 'término do trabalho')) {
            return 'pendencia de obra';
        }

        return 'assistencia geral';
    }

    protected function respondeLacunaAtual(?string $lacuna, array $resultado): bool
    {
        if (! $lacuna) {
            return false;
        }

        return match ($lacuna) {
            'nome' => ! empty($resultado['nome']),
            'cep', 'localizacao' => ! empty($resultado['cep']) || ! empty($resultado['bairro']) || ! empty($resultado['cidade']),
            'solucao_principal' => ! empty($resultado['solucao_principal']),
            'tipo_imovel' => ! empty($resultado['tipo_imovel']),
            'area_projeto' => ! empty($resultado['area_projeto']),
            'medida_ou_midia' => (! empty($resultado['largura']) && ! empty($resultado['comprimento'])) || ! empty($resultado['tem_foto']) || ! empty($resultado['tem_video']) || ! empty($resultado['tem_projeto']),
            'principal_desejo' => ! empty($resultado['principal_desejo']),
            'prioridade_atual' => ! empty($resultado['prioridade_atual']),
            default => false,
        };
    }

    protected function trouxeDadoNovo(array $resultado): bool
    {
        $campos = [
            'nome', 'telefone', 'email', 'perfil_contato', 'cep', 'bairro', 'cidade',
            'solucao_principal', 'solucao_subtipo', 'tipo_imovel', 'area_projeto',
            'contexto_uso', 'largura', 'comprimento', 'area_informada_m2',
            'principal_desejo', 'urgencia', 'objecao_principal', 'estagio_decisao',
        ];

        foreach ($campos as $campo) {
            if (! empty($resultado[$campo])) {
                return true;
            }
        }

        if (! empty($resultado['prioridade_atual'])) {
            return true;
        }

        if (! empty($resultado['tem_foto']) || ! empty($resultado['tem_video']) || ! empty($resultado['tem_projeto'])) {
            return true;
        }

        return false;
    }

    protected function mudouAssunto(array $resultado, array $contexto): bool
    {
        if (! empty($resultado['assistencia']) || ! empty($resultado['fora_escopo'])) {
            return true;
        }

        $estadoAtual = $contexto['estado_atual'] ?? null;

        if ($estadoAtual === 'L2' || $estadoAtual === 'L1') {
            return false;
        }

        return false;
    }

    protected function ajustarConfianca(array &$resultado, string $textoOriginal, string $texto): void
    {
        $camposBaixa = [];

        if (! empty($resultado['nome']) && ! $this->nomeVeioPorPadraoClaro($textoOriginal, $texto)) {
            $camposBaixa[] = 'nome';
        }

        if (! empty($resultado['bairro']) && mb_strlen((string) $resultado['bairro']) < 4) {
            $camposBaixa[] = 'bairro';
        }

        if (
            empty($resultado['nome']) &&
            empty($resultado['solucao_principal']) &&
            empty($resultado['area_projeto']) &&
            empty($resultado['tipo_imovel']) &&
            empty($resultado['largura']) &&
            empty($resultado['principal_desejo']) &&
            empty($resultado['objecao_principal']) &&
            empty($resultado['assistencia']) &&
            empty($resultado['fora_escopo'])
        ) {
            $resultado['confianca_geral'] = 'baixa';
        } elseif (! empty($camposBaixa)) {
            $resultado['confianca_geral'] = 'media';
        } else {
            $resultado['confianca_geral'] = 'alta';
        }

        $resultado['campos_baixa_confianca'] = $camposBaixa;
    }

    protected function nomeVeioPorPadraoClaro(string $textoOriginal, string $texto): bool
    {
        return preg_match('/(meu nome e|me chamo|sou o|sou a)/iu', $textoOriginal) === 1;
    }

    protected function isGreeting(string $texto): bool
    {
        $saudacoes = ['oi', 'ola', 'olá', 'bom dia', 'boa tarde', 'boa noite', 'tudo bem'];

        foreach ($saudacoes as $saudacao) {
            if (str_contains($texto, $this->normalize($saudacao))) {
                return true;
            }
        }

        return false;
    }

    protected function isUrgencia(string $texto): bool
    {
        return $this->extractUrgencia($texto) !== null;
    }

    protected function isPedidoPreco(string $texto): bool
    {
        return str_contains($texto, 'quanto custa')
            || str_contains($texto, 'qual o valor')
            || str_contains($texto, 'me passa o valor')
            || str_contains($texto, 'me manda um orcamento')
            || str_contains($texto, 'me manda um orçamento');
    }

    protected function isPedidoPrazo(string $texto): bool
    {
        return str_contains($texto, 'qual o prazo')
            || str_contains($texto, 'em quanto tempo')
            || str_contains($texto, 'dá tempo')
            || str_contains($texto, 'da tempo')
            || str_contains($texto, 'preciso para');
    }

    protected function isPedidoVisita(string $texto): bool
    {
        return str_contains($texto, 'agendar visita')
            || str_contains($texto, 'marcar visita')
            || str_contains($texto, 'pode vir')
            || str_contains($texto, 'quinta as 10')
            || str_contains($texto, 'quinta às 10');
    }

    protected function isObjecao(string $texto): bool
    {
        return $this->extractObjecao($texto) !== null;
    }

    protected function isFormalizacao(string $texto): bool
    {
        return str_contains($texto, 'fechado')
            || str_contains($texto, 'pode fazer o contrato')
            || str_contains($texto, 'vou mandar meus dados');
    }

    protected function normalize(string $texto): string
    {
        $texto = trim(Str::lower($texto));
        $texto = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto) ?: $texto;
        $texto = preg_replace('/\s+/', ' ', $texto);

        return trim($texto);
    }

    protected function normalizeNumber(string $valor): string
    {
        return str_replace(',', '.', trim($valor));
    }

    protected function sanitizeNome(string $nome): string
    {
        $nome = preg_replace('/\s+/', ' ', trim($nome));
        return Str::title(Str::lower($nome));
    }

    protected function isNomeProvavel(string $nome): bool
    {
        $nome = trim($nome);

        if ($nome === '') {
            return false;
        }

        $bloqueios = [
            'arquiteta',
            'arquiteto',
            'engenheiro',
            'engenheira',
            'cliente',
            'empresa',
            'assistente',
        ];

        return ! in_array($this->normalize($nome), $bloqueios, true);
    }
}
