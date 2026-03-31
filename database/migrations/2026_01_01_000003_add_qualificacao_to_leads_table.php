<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('origem')->nullable();
            $table->string('interesse')->nullable();
            $table->string('urgencia')->nullable();
            $table->string('temperatura')->default('frio');
            $table->integer('score')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn([
                'origem',
                'interesse',
                'urgencia',
                'temperatura',
                'score'
            ]);
        });
    }
};
