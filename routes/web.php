<?php

use App\Models\Lead;
use App\Models\LeadInteraction;
use App\Services\LeadMemoryExtractorService;
use App\Services\OpenAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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
    LeadDecisionEngineService $decisionEngine
) {
    $request->validate([
        'mensagem_ia' => ['required', 'string'],
    ]);

    $lead = Lead::query()->with('interactions')->findOrFail($id);

    $mensagem = $request->input('mensagem_ia');

    $memoryExtractor->extrairEAtualizar($lead, $mensagem);
    $decisionEngine->processar($lead, $mensagem);

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

    $resposta = $openAIService->responderLead(
        $mensagem,
        array_merge($fatos, [
            'historico' => $historico,
        ])
    );

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
