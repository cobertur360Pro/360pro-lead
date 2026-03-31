<?php

use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Services\LeadMemoryExtractorService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Services\LeadDecisionEngineService;
use App\Services\Lead360GuardrailsService;
use App\Services\Lead360StageService;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/painel', function () {
    $totalLeads = Lead::count();
    $totalNovo = Lead::where('status', 'novo')->count();
    $totalContato = Lead::where('status', 'contato')->count();
    $totalOrcamento = Lead::where('status', 'orcamento')->count();
    $totalFechado = Lead::where('status', 'fechado')->count();
    $totalPerdido = Lead::where('status', 'perdido')->count();

    return view('painel.index', compact(
        'totalLeads',
        'totalNovo',
        'totalContato',
        'totalOrcamento',
        'totalFechado',
        'totalPerdido'
    ));
})->name('painel.index');

Route::get('/leads', function () {
    $leads = Lead::query()->latest()->get();

    return view('leads.index', [
        'leads' => $leads,
    ]);
})->name('leads.index');

Route::post('/leads', function (Request $request) {
    $request->validate([
        'nome' => ['required', 'string', 'max:255'],
        'telefone' => ['nullable', 'string', 'max:255'],
        'cidade' => ['nullable', 'string', 'max:255'],
    ]);

    Lead::create([
        'nome' => $request->input('nome'),
        'telefone' => $request->input('telefone'),
        'cidade' => $request->input('cidade'),
        'status' => 'novo',
    ]);

    return redirect()->route('leads.index');
})->name('leads.store');

Route::get('/leads/{id}', function ($id) {
    $lead = Lead::query()->with('interactions')->findOrFail($id);

    return view('leads.show', compact('lead'));
})->name('leads.show');

Route::post('/leads/{id}/observacoes', function ($id, Request $request) {
    $request->validate([
        'observacoes' => ['nullable', 'string'],
    ]);

    $lead = Lead::query()->findOrFail($id);
    $lead->observacoes = $request->input('observacoes');
    $lead->save();

    return redirect()->route('leads.show', $lead->id);
})->name('leads.observacoes');

Route::post('/leads/{id}/interacoes', function ($id, Request $request) {
    $request->validate([
        'tipo' => ['required', 'string', 'max:100'],
        'conteudo' => ['required', 'string'],
    ]);

    $lead = Lead::query()->findOrFail($id);

    LeadInteraction::create([
        'lead_id' => $lead->id,
        'tipo' => $request->input('tipo'),
        'conteudo' => $request->input('conteudo'),
    ]);

    return redirect()->route('leads.show', $lead->id);
})->name('leads.interacoes.store');

Route::post('/leads/{id}/qualificacao', function ($id, Request $request) {
    $lead = Lead::findOrFail($id);

    $lead->origem = $request->origem;
    $lead->interesse = $request->interesse;
    $lead->urgencia = $request->urgencia;

    $lead->save();
    $lead->atualizarQualificacao();

    return redirect()->route('leads.show', $lead->id);
})->name('leads.qualificacao');

Route::post('/leads/{id}/ia', function (
    $id,
    Request $request,
    OpenAIService $openAIService,
    LeadMemoryExtractorService $memoryExtractor,
    LeadDecisionEngineService $decisionEngine,
    Lead360GuardrailsService $guardrails,
    Lead360StageService $stageService
) {
    $request->validate([
        'mensagem_ia' => ['required', 'string'],
    ]);

    $lead = Lead::query()->with('interactions')->findOrFail($id);

    $mensagem = $request->input('mensagem_ia');
    $intentService = app(\App\Services\Lead360IntentService::class);
    $intencao = $intentService->detectar($mensagem);
    
    $resposta = null;
    
    if ($intencao === 'saudacao') {
        $resposta = 'Oi! Tudo bem 🙂 Sou o assistente da Baumann e vou te ajudar a encontrar a melhor solução. Me conta: você está buscando cobertura, fechamento, sacada ou algo nessa linha?';
    }
    
    elseif ($intencao === 'identidade') {
        $resposta = 'Eu sou o assistente virtual da Baumann, criado para te orientar de forma rápida e correta. Se precisar, posso encaminhar você para um especialista também. Me conta: o que você está buscando no seu projeto?';
    }
    
    elseif ($intencao === 'duvida') {
        $resposta = 'Sem problema, eu te explico melhor 🙂 Vou te fazer algumas perguntas rápidas para entender seu projeto e te orientar da forma certa. Primeiro: você está buscando cobertura, fechamento, sacada ou algo nessa linha?';
    }

    $memoryExtractor->extrairEAtualizar($lead, $mensagem);

    if ($guardrails->qualificacaoHabilitada()) {
        $decisionEngine->processar($lead, $mensagem);
    }

    $lead->refresh();
    $lead->load('interactions');

    $historico = $lead->interactions
        ->take(10)
        ->reverse()
        ->map(function ($i) {
            return [
                'pergunta' => $i->conteudo,
                'resposta' => $i->resposta_ia,
            ];
        })
               ->values()
        ->toArray();

    $fatos = $lead->fatosConfirmados();

    $resposta = null;

   if ($guardrails->qualificacaoHabilitada()) {
    $decisionEngine->processar($lead, $mensagem);
}

$lead->refresh();
$lead->load('interactions');

$historico = $lead->interactions
    ->take(10)
    ->reverse()
    ->map(function ($i) {
        return [
            'pergunta' => $i->conteudo,
            'resposta' => $i->resposta_ia,
        ];
    })
    ->values()
    ->toArray();

$fatos = $lead->fatosConfirmados();

$intentService = app(\App\Services\Lead360IntentService::class);
$intencao = $intentService->detectar($mensagem);

$resposta = null;

// 1) Primeiro trata intenção básica da conversa
if ($intencao === 'saudacao') {
    $resposta = 'Oi! Tudo bem 🙂 Sou o assistente da Baumann e vou te ajudar a encontrar a melhor solução. Me conta: você está buscando cobertura, fechamento, sacada ou algo nessa linha?';
} elseif ($intencao === 'identidade') {
    $resposta = 'Eu sou o assistente virtual da Baumann, criado para te orientar de forma rápida e correta. Se precisar, também posso encaminhar você para um especialista. Me conta: o que você está buscando no seu projeto?';
} elseif ($intencao === 'duvida') {
    $resposta = 'Sem problema, eu te explico melhor 🙂 Vou te fazer algumas perguntas rápidas para entender seu projeto e te orientar da forma certa. Primeiro: você está buscando cobertura, fechamento, sacada ou algo nessa linha?';
}

// 2) Só entra no fluxo guiado se ainda não tiver resposta
if (! $resposta && $guardrails->fluxoGuiadoAtivo()) {
    $proximaPergunta = $stageService->proximaPergunta($lead);

    if ($proximaPergunta) {
        $resposta = $proximaPergunta;
    }
}

// 3) Só chama OpenAI se ainda não tiver resposta
if (! $resposta) {
    $resposta = $openAIService->responderLead(
        $mensagem,
        array_merge($fatos, [
            'historico' => $historico,
        ])
    );
}

$humanizer = app(\App\Services\Lead360HumanizerService::class);
$resposta = $humanizer->humanizar($resposta);

LeadInteraction::create([
    'lead_id' => $lead->id,
    'tipo' => 'ia_teste',
    'conteudo' => $mensagem,
    'resposta_ia' => $resposta,
]);

return redirect()->route('leads.show', $lead->id);
})->name('leads.ia');

Route::post('/leads/status/{id}', function ($id) {
    $lead = Lead::query()->findOrFail($id);

    $statuses = Lead::statusList();
    $currentIndex = array_search($lead->status, $statuses, true);

    if ($currentIndex === false) {
        $lead->status = 'novo';
    } elseif ($currentIndex < count($statuses) - 1) {
        $lead->status = $statuses[$currentIndex + 1];
    }

    $lead->save();
    $lead->atualizarQualificacao();

    return redirect()->route('leads.index');
})->name('leads.status');

Route::post('/leads/delete/{id}', function ($id) {
    Lead::query()->findOrFail($id)->delete();

    return redirect()->route('leads.index');
})->name('leads.delete');

Route::get('/conversas', function () {
    $interactions = LeadInteraction::query()->with('lead')->latest()->get();

    return view('conversas.index', compact('interactions'));
})->name('conversas.index');

Route::get('/configuracoes', function () {
    return view('configuracoes.index');
})->name('configuracoes.index');

Route::get('/teste-helpers', function () {
    return [
        'tenant_id' => tenant_id(),
        'modulo_LD001' => modulo_habilitado('LD001'),
        'modulo_LD007' => modulo_habilitado('LD007'),
        'param_LDA_001' => param_bool('LDA-001'),
        'param_LDA_011' => param_bool('LDA-011'),
        'param_LDA_034' => param_text('LDA-034'),
    ];
});

Route::get('/teste-ia-controlada', function (
    \App\Services\OpenAIService $openAIService,
    \App\Services\Lead360GuardrailsService $guardrails
) {
    return [
        'atendimento_ia_habilitado' => $guardrails->atendimentoIaHabilitado(),
        'openai_habilitada' => $guardrails->openAiHabilitada(),
        'pode_agendar_diretamente' => $guardrails->iaPodeAgendarDiretamente(),
        'pode_gerar_orcamento_diretamente' => $guardrails->iaPodeGerarOrcamentoDiretamente(),
        'pode_falar_preco_sem_contexto' => $guardrails->iaPodeFalarPrecoSemContexto(),
        'mensagem_fora_escopo' => $openAIService->responderLead('Vocês fazem toldo?'),
    ];
});

Route::get('/teste-fluxo-lead/{id}', function (
    $id,
    \App\Services\Lead360StageService $stageService
) {
    $lead = \App\Models\Lead::findOrFail($id);

    return [
        'lead_id' => $lead->id,
        'nome' => $lead->nome,
        'tipo_projeto' => $lead->tipo_projeto,
        'tipo_imovel' => $lead->tipo_imovel,
        'interesse' => $lead->interesse,
        'bairro' => $lead->bairro,
        'cidade' => $lead->cidade,
        'largura' => $lead->largura,
        'comprimento' => $lead->comprimento,
        'estrutura_existente' => $lead->estrutura_existente,
        'proxima_pergunta' => $stageService->proximaPergunta($lead),
        'contexto_minimo_fechado' => $stageService->contextoMinimoFechado($lead),
    ];
});
