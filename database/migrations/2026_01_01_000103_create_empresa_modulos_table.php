<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresa_modulos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->unsignedBigInteger('modulo_id');
            $table->boolean('habilitado')->default(false);
            $table->timestamps();

            $table->unique(['empresa_id', 'modulo_id'], 'uniq_empresa_modulo');
        });

        Schema::table('empresa_modulos', function (Blueprint $table) {
            $table->foreign('empresa_id', 'empresa_modulos_empresa_id_foreign')
                ->references('id')
                ->on('empresas')
                ->onDelete('cascade');

            $table->foreign('modulo_id', 'empresa_modulos_modulo_id_foreign')
                ->references('id')
                ->on('modulos')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_modulos', function (Blueprint $table) {
            $table->dropForeign('empresa_modulos_empresa_id_foreign');
            $table->dropForeign('empresa_modulos_modulo_id_foreign');
        });

        Schema::dropIfExists('empresa_modulos');
    }
};
