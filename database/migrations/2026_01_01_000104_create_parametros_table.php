<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametros', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('bloco', 20);
            $table->string('camada', 30)->default('core');
            $table->string('escopo', 30)->default('empresa');
            $table->string('tipo', 20);
            $table->text('valor_padrao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametros');
    }
};
