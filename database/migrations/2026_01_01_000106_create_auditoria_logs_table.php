<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditoria_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->nullable()->constrained('empresas')->nullOnDelete();
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('acao', 100);
            $table->string('entidade', 100);
            $table->unsignedBigInteger('entidade_id')->nullable();
            $table->longText('dados_antes')->nullable();
            $table->longText('dados_depois')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_logs');
    }
};
