<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'nome']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('animais', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('brinco', 50);
            $table->string('nome_ou_numero', 100)->nullable();
            $table->enum('categoria', ['BEZERRO', 'BEZERRA', 'GARROTE', 'NOVILHO', 'NOVILHA', 'BOI', 'VACA', 'TOURO']);
            $table->string('raca', 100)->nullable();
            $table->enum('sexo', ['MACHO', 'FEMEA']);
            $table->date('data_nascimento')->nullable();
            $table->enum('status', ['ATIVO', 'VENDIDO', 'MORTO', 'DESCARTADO', 'TRANSFERIDO'])->default('ATIVO');
            $table->unsignedBigInteger('lote_id')->nullable();
            $table->softDeletesTz();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'brinco']);
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'status', 'categoria']);
            $table->index(['tenant_id', 'lote_id']);
            $table->foreign(['tenant_id', 'lote_id'])->references(['tenant_id', 'id'])->on('lotes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('animais');
        Schema::dropIfExists('lotes');
    }
};
