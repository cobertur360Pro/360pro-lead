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
    Lead::create($request->all());
    return redirect()->back();
})->name('leads.store');

Route::get('/conversas', function () {
    return view('conversas.index');
});

Route::get('/configuracoes', function () {
    return view('configuracoes.index');
});
