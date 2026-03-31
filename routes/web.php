<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/painel', function () {
    return view('painel.index');
})->name('painel.index');

Route::get('/leads', function () {
    return view('leads.index');
})->name('leads.index');

Route::get('/conversas', function () {
    return view('conversas.index');
})->name('conversas.index');

Route::get('/configuracoes', function () {
    return view('configuracoes.index');
})->name('configuracoes.index');
