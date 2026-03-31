<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_interactions', function (Blueprint $table) {
            $table->longText('resposta_ia')->nullable()->after('conteudo');
        });
    }

    public function down(): void
    {
        Schema::table('lead_interactions', function (Blueprint $table) {
            $table->dropColumn('resposta_ia');
        });
    }
};
