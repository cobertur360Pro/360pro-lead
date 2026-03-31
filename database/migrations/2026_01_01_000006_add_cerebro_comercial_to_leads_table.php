<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('perfil_cliente')->nullable()->after('material_desejado');
            $table->string('fase_funil')->nullable()->after('perfil_cliente');
            $table->string('urgencia_real')->nullable()->after('fase_funil');
            $table->string('preferencia_estetica')->nullable()->after('urgencia_real');
            $table->string('objecao_principal')->nullable()->after('preferencia_estetica');
            $table->string('medo_principal')->nullable()->after('objecao_principal');
            $table->string('motivo_compra')->nullable()->after('medo_principal');
            $table->string('restricao_orcamento')->nullable()->after('motivo_compra');
            $table->string('restricao_prazo')->nullable()->after('restricao_orcamento');
            $table->boolean('cliente_tecnico')->default(false)->after('restricao_prazo');
            $table->boolean('cliente_existente')->default(false)->after('cliente_tecnico');
            $table->string('proxima_acao')->nullable()->after('cliente_existente');
            $table->date('data_followup')->nullable()->after('proxima_acao');
            $table->text('resumo_contexto')->nullable()->after('data_followup');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'perfil_cliente',
                'fase_funil',
                'urgencia_real',
                'preferencia_estetica',
                'objecao_principal',
                'medo_principal',
                'motivo_compra',
                'restricao_orcamento',
                'restricao_prazo',
                'cliente_tecnico',
                'cliente_existente',
                'proxima_acao',
                'data_followup',
                'resumo_contexto',
            ]);
        });
    }
};
