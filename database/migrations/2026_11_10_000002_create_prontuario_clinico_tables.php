<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('doencas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('nome', 150);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'nome']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('casos_clinicos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('animal_id');
            $table->string('titulo', 150);
            $table->date('data_inicio');
            $table->text('sintomas_iniciais');
            $table->enum('status', ['ABERTO', 'EM_ACOMPANHAMENTO', 'ENCERRADO'])->default('ABERTO');
            $table->date('data_encerramento')->nullable();
            $table->enum('desfecho', ['RECUPERADO', 'DIAGNOSTICO_DESCARTADO', 'TRANSFERIDO', 'OBITO', 'OUTRO'])->nullable();
            $table->text('observacao_encerramento')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->index(['tenant_id', 'animal_id', 'status']);
            $table->index(['tenant_id', 'status', 'data_inicio']);
            $table->foreign(['tenant_id', 'animal_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
        });

        Schema::create('avaliacoes_clinicas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('caso_clinico_id');
            $table->date('data_avaliacao');
            $table->string('veterinario', 150);
            $table->text('sintomas')->nullable();
            $table->text('parecer');
            $table->text('orientacao')->nullable();
            $table->unsignedBigInteger('doenca_id')->nullable();
            $table->enum('situacao_diagnostico', ['SUSPEITO', 'CONFIRMADO', 'DESCARTADO', 'INCONCLUSIVO'])->nullable();
            $table->timestampsTz();
            $table->index(['tenant_id', 'caso_clinico_id', 'data_avaliacao'], 'avaliacoes_caso_data_index');
            $table->index(['tenant_id', 'doenca_id']);
            $table->foreign(['tenant_id', 'caso_clinico_id'])->references(['tenant_id', 'id'])->on('casos_clinicos')->restrictOnDelete();
            $table->foreign(['tenant_id', 'doenca_id'])->references(['tenant_id', 'id'])->on('doencas')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE casos_clinicos ADD CONSTRAINT casos_clinicos_encerramento_check CHECK (
                (status = 'ENCERRADO' AND data_encerramento IS NOT NULL AND data_encerramento >= data_inicio
                    AND desfecho IS NOT NULL AND observacao_encerramento IS NOT NULL AND btrim(observacao_encerramento) <> '')
                OR (status <> 'ENCERRADO' AND data_encerramento IS NULL AND desfecho IS NULL AND observacao_encerramento IS NULL)
            )");
            DB::statement('ALTER TABLE avaliacoes_clinicas ADD CONSTRAINT avaliacoes_vinculo_diagnostico_check
                CHECK ((doenca_id IS NULL) = (situacao_diagnostico IS NULL))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('avaliacoes_clinicas');
        Schema::dropIfExists('casos_clinicos');
        Schema::dropIfExists('doencas');
    }
};
