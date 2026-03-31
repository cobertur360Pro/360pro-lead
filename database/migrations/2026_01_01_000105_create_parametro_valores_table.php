<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametro_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parametro_id')->constrained('parametros')->cascadeOnDelete();
            $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
            $table->text('valor')->nullable();
            $table->timestamps();

            $table->unique(['parametro_id', 'empresa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametro_valores');
    }
};
