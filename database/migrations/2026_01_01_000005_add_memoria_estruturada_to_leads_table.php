<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('bairro')->nullable()->after('cidade');
            $table->string('tipo_imovel')->nullable()->after('bairro');
            $table->string('tipo_projeto')->nullable()->after('tipo_imovel');
            $table->string('largura')->nullable()->after('tipo_projeto');
            $table->string('comprimento')->nullable()->after('largura');
            $table->string('estrutura_existente')->nullable()->after('comprimento');
            $table->string('material_desejado')->nullable()->after('estrutura_existente');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'bairro',
                'tipo_imovel',
                'tipo_projeto',
                'largura',
                'comprimento',
                'estrutura_existente',
                'material_desejado',
            ]);
        });
    }
};
