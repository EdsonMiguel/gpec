<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contas_orcamentarias', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('codigo', 50);
            $table->string('nome', 150);
            $table->enum('natureza', ['RECEITA', 'DESPESA']);
            $table->decimal('valor_orcado', 14, 2)->default(0);
            $table->date('periodo_inicio');
            $table->date('periodo_fim');
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'codigo']);
            $table->unique(['tenant_id', 'id', 'natureza'], 'contas_orcamentarias_tenant_id_natureza_unique');
        });
        Schema::create('produtos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('codigo', 50);
            $table->string('nome', 150);
            $table->string('unidade', 10);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'codigo']);
            $table->unique(['tenant_id', 'id']);
        });
        Schema::create('transacoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('conta_orcamentaria_id');
            $table->enum('natureza', ['RECEITA', 'DESPESA']);
            $table->enum('tipo', ['COMPRA', 'VENDA']);
            $table->string('descricao', 255);
            $table->string('pessoa_nome', 150);
            $table->string('documento', 100)->nullable();
            $table->date('data_transacao');
            $table->date('data_vencimento')->nullable();
            $table->enum('status', ['PENDENTE', 'EFETIVADA', 'CANCELADA']);
            $table->date('data_efetivacao')->nullable();
            $table->string('forma_pagamento', 30)->nullable();
            $table->decimal('valor_total', 14, 2);
            $table->string('moeda', 3)->default('BRL');
            $table->string('chave_idempotencia', 64);
            $table->string('conteudo_hash', 64);
            $table->date('data_cancelamento')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->text('observacao')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'chave_idempotencia']);
            $table->index(['tenant_id', 'data_transacao', 'status']);
            $table->foreign(['tenant_id', 'conta_orcamentaria_id', 'natureza'], 'transacoes_conta_natureza_foreign')
                ->references(['tenant_id', 'id', 'natureza'])->on('contas_orcamentarias')->restrictOnDelete();
        });
        Schema::create('itens_transacao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('transacao_id');
            $table->enum('tipo_item', ['ALIMENTO', 'MEDICAMENTO', 'ANIMAL', 'PRODUTO', 'OUTRO']);
            $table->unsignedBigInteger('alimento_id')->nullable();
            $table->unsignedBigInteger('medicamento_id')->nullable();
            $table->unsignedBigInteger('animal_id')->nullable();
            $table->unsignedBigInteger('produto_id')->nullable();
            $table->string('descricao', 255);
            $table->string('unidade', 10);
            $table->decimal('quantidade', 12, 3);
            $table->decimal('valor_unitario', 14, 2);
            $table->decimal('valor_total', 14, 2);
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'transacao_id'])->references(['tenant_id', 'id'])->on('transacoes')->restrictOnDelete();
            foreach (['alimento' => 'alimentos', 'medicamento' => 'medicamentos', 'animal' => 'animais', 'produto' => 'produtos'] as $column => $target) {
                $table->index(['tenant_id', $column.'_id']);
                $table->foreign(['tenant_id', $column.'_id'])->references(['tenant_id', 'id'])->on($target)->restrictOnDelete();
            }
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE contas_orcamentarias ADD CONSTRAINT contas_orcamento_check CHECK (valor_orcado >= 0 AND periodo_fim >= periodo_inicio)');
            DB::statement("ALTER TABLE transacoes ADD CONSTRAINT transacoes_valores_check CHECK (valor_total > 0 AND moeda = 'BRL' AND ((tipo = 'COMPRA' AND natureza = 'DESPESA') OR (tipo = 'VENDA' AND natureza = 'RECEITA')))");
            DB::statement('ALTER TABLE transacoes ADD CONSTRAINT transacoes_datas_check CHECK ((data_vencimento IS NULL OR data_vencimento >= data_transacao) AND (data_efetivacao IS NULL OR data_efetivacao >= data_transacao) AND (data_cancelamento IS NULL OR data_cancelamento >= data_transacao))');
            DB::statement("ALTER TABLE transacoes ADD CONSTRAINT transacoes_ciclo_vida_check CHECK ((status = 'PENDENTE' AND data_efetivacao IS NULL AND data_cancelamento IS NULL AND motivo_cancelamento IS NULL) OR (status = 'EFETIVADA' AND data_efetivacao IS NOT NULL AND forma_pagamento IS NOT NULL AND data_cancelamento IS NULL AND motivo_cancelamento IS NULL) OR (status = 'CANCELADA' AND data_cancelamento IS NOT NULL AND motivo_cancelamento IS NOT NULL))");
            DB::statement('ALTER TABLE itens_transacao ADD CONSTRAINT itens_transacao_valores_check CHECK (quantidade > 0 AND valor_unitario > 0 AND valor_total > 0 AND valor_total = round(quantidade * valor_unitario, 2))');
            DB::statement("ALTER TABLE itens_transacao ADD CONSTRAINT itens_transacao_vinculo_check CHECK ((tipo_item = 'ALIMENTO' AND alimento_id IS NOT NULL AND medicamento_id IS NULL AND animal_id IS NULL AND produto_id IS NULL) OR (tipo_item = 'MEDICAMENTO' AND medicamento_id IS NOT NULL AND alimento_id IS NULL AND animal_id IS NULL AND produto_id IS NULL) OR (tipo_item = 'ANIMAL' AND animal_id IS NOT NULL AND alimento_id IS NULL AND medicamento_id IS NULL AND produto_id IS NULL AND quantidade = 1) OR (tipo_item = 'PRODUTO' AND produto_id IS NOT NULL AND alimento_id IS NULL AND medicamento_id IS NULL AND animal_id IS NULL) OR (tipo_item = 'OUTRO' AND alimento_id IS NULL AND medicamento_id IS NULL AND animal_id IS NULL AND produto_id IS NULL))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_transacao');
        Schema::dropIfExists('transacoes');
        Schema::dropIfExists('produtos');
        Schema::dropIfExists('contas_orcamentarias');
    }
};
