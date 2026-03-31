<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\Lead;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/painel', function () {
    return view('painel.index');
});

Route::get('/leads', function () {
    $leads = Lead::latest()->get();
    return view('leads.index', compact('leads'));
})->name('leads.index');

Route::post('/leads', function (Request $request) {
    Lead::create([
        'nome' => $request->nome,
        'telefone' => $request->telefone,
        'cidade' => $request->cidade,
        'status' => 'novo'
    ]);

    return redirect()->back();
})->name('leads.store');

Route::post('/leads/status/{id}', function ($id) {
    $lead = Lead::findOrFail($id);

    $statuses = Lead::statusList();
    $currentIndex = array_search($lead->status, $statuses);

    if ($currentIndex < count($statuses) - 1) {
        $lead->status = $statuses[$currentIndex + 1];
        $lead->save();
    }

    return redirect()->back();
})->name('leads.status');

Route::post('/leads/delete/{id}', function ($id) {
    Lead::findOrFail($id)->delete();
    return redirect()->back();
})->name('leads.delete');

Route::get('/conversas', function () {
    return view('conversas.index');
});

Route::get('/configuracoes', function () {
    return view('configuracoes.index');
});
