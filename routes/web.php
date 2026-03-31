<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Lead360 AI
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('home');
});

Route::get('/painel', function () {
    return view('painel.index');
});

Route::get('/leads', function () {
    return view('leads.index');
});

Route::get('/conversas', function () {
    return view('conversas.index');
});

Route::get('/configuracoes', function () {
    return view('configuracoes.index');
});
