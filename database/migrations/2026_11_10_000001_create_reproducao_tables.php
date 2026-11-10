<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gestacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('matriz_id');
            $table->unsignedBigInteger('reprodutor_id')->nullable();
            $table->enum('tipo', ['MONTA', 'INSEMINACAO']);
            $table->date('data_cobertura');
            $table->string('referencia_semen', 100)->nullable();
            $table->string('responsavel', 150)->nullable();
            $table->text('observacao')->nullable();
            $table->enum('status', ['AGUARDANDO_EXAME', 'CONFIRMADA', 'SEM_PRENHEZ', 'ENCERRADA', 'PERDA', 'PARTO']);
            $table->date('data_confirmacao')->nullable();
            $table->date('data_prevista_parto')->nullable();
            $table->date('data_encerramento')->nullable();
            $table->text('observacao_encerramento')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'matriz_id', 'data_cobertura']);
            $table->index(['tenant_id', 'reprodutor_id']);
            $table->index(['tenant_id', 'status', 'data_prevista_parto']);
            $table->foreign(['tenant_id', 'matriz_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
            $table->foreign(['tenant_id', 'reprodutor_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('gestacoes', function (Blueprint $table): void {
                $table->unsignedBigInteger('matriz_ciclo_aberto_id')
                    ->storedAs("CASE WHEN status IN ('AGUARDANDO_EXAME', 'CONFIRMADA') THEN matriz_id ELSE NULL END");
                $table->unique(['tenant_id', 'matriz_ciclo_aberto_id'], 'gestacoes_matriz_ciclo_aberto');
            });
        } else {
            DB::statement("CREATE UNIQUE INDEX gestacoes_matriz_ciclo_aberto ON gestacoes (tenant_id, matriz_id) WHERE status IN ('AGUARDANDO_EXAME', 'CONFIRMADA')");
        }

        Schema::create('exames_gestacao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('gestacao_id');
            $table->date('data_exame');
            $table->string('metodo', 100);
            $table->enum('resultado', ['POSITIVO', 'NEGATIVO', 'INCONCLUSIVO']);
            $table->string('responsavel', 150)->nullable();
            $table->text('observacao')->nullable();
            $table->timestampsTz();
            $table->index(['tenant_id', 'gestacao_id', 'data_exame']);
            $table->foreign(['tenant_id', 'gestacao_id'])->references(['tenant_id', 'id'])->on('gestacoes')->restrictOnDelete();
        });

        Schema::table('animais', function (Blueprint $table): void {
            $table->unsignedBigInteger('gestacao_nascimento_id')->nullable();
            $table->index(['tenant_id', 'gestacao_nascimento_id']);
            $table->foreign(['tenant_id', 'gestacao_nascimento_id'])->references(['tenant_id', 'id'])->on('gestacoes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('animais', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id', 'gestacao_nascimento_id']);
            $table->dropIndex(['tenant_id', 'gestacao_nascimento_id']);
            $table->dropColumn('gestacao_nascimento_id');
        });

        Schema::dropIfExists('exames_gestacao');
        Schema::dropIfExists('gestacoes');
    }
};
