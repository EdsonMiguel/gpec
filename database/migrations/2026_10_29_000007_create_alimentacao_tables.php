<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_alimento', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('codigo', 40);
            $table->string('nome', 100);
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'codigo']);
            $table->unique(['tenant_id', 'nome']);
        });
        Schema::create('alimentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('tipo_alimento_id');
            $table->string('codigo', 50);
            $table->string('nome', 150);
            $table->enum('unidade', ['KG', 'G', 'L', 'ML']);
            $table->text('composicao')->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'codigo']);
            $table->foreign(['tenant_id', 'tipo_alimento_id'])->references(['tenant_id', 'id'])->on('tipos_alimento')->restrictOnDelete();
        });
        Schema::create('refeicoes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('animal_id');
            $table->timestampTz('data_fornecimento');
            $table->enum('turno', ['MANHA', 'TARDE', 'NOITE', 'OUTRO']);
            $table->text('observacao')->nullable();
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'animal_id', 'data_fornecimento']);
            $table->index(['tenant_id', 'data_fornecimento']);
            $table->foreign(['tenant_id', 'animal_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
        });
        Schema::create('itens_refeicao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('refeicao_id');
            $table->unsignedBigInteger('alimento_id');
            $table->decimal('quantidade', 12, 3);
            $table->string('unidade', 10);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'refeicao_id', 'alimento_id']);
            $table->index(['tenant_id', 'alimento_id']);
            $table->foreign(['tenant_id', 'refeicao_id'])->references(['tenant_id', 'id'])->on('refeicoes')->restrictOnDelete();
            $table->foreign(['tenant_id', 'alimento_id'])->references(['tenant_id', 'id'])->on('alimentos')->restrictOnDelete();
        });
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE itens_refeicao ADD CONSTRAINT itens_refeicao_quantidade_check CHECK (quantidade > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_refeicao');
        Schema::dropIfExists('refeicoes');
        Schema::dropIfExists('alimentos');
        Schema::dropIfExists('tipos_alimento');
    }
};
