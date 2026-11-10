<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pesagens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('animal_id');
            $table->decimal('peso', 7, 2);
            $table->timestampTz('data_pesagem');
            $table->text('observacao')->nullable();
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'animal_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
            $table->unique(['tenant_id', 'animal_id', 'data_pesagem']);
            $table->index(['tenant_id', 'data_pesagem']);
        });

        Schema::create('medicamentos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('principio_ativo', 150)->nullable();
            $table->enum('unidade', ['ML', 'G', 'MG', 'DOSE']);
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'nome']);
            $table->unique(['tenant_id', 'id']);
        });

        Schema::create('manejos_sanitarios', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('animal_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->enum('tipo', ['VACINA', 'VERMIFUGO', 'TRATAMENTO', 'OUTRO']);
            $table->decimal('dosagem', 10, 3);
            $table->string('unidade', 10);
            $table->date('data_aplicacao');
            $table->date('proxima_aplicacao')->nullable();
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'animal_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
            $table->foreign(['tenant_id', 'medicamento_id'])->references(['tenant_id', 'id'])->on('medicamentos')->restrictOnDelete();
            $table->index(['tenant_id', 'proxima_aplicacao']);
            $table->index(['tenant_id', 'animal_id', 'medicamento_id', 'data_aplicacao'], 'manejos_animal_produto_data_index');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE pesagens ADD CONSTRAINT pesagens_peso_check CHECK (peso > 0 AND peso < 100000)');
            DB::statement('ALTER TABLE manejos_sanitarios ADD CONSTRAINT manejos_dosagem_check CHECK (dosagem > 0)');
            DB::statement('ALTER TABLE manejos_sanitarios ADD CONSTRAINT manejos_proxima_check CHECK (proxima_aplicacao IS NULL OR proxima_aplicacao > data_aplicacao)');
            DB::statement("ALTER TABLE animais ADD CONSTRAINT animais_alocacao_check CHECK (status = 'ATIVO' OR lote_id IS NULL)");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manejos_sanitarios');
        Schema::dropIfExists('medicamentos');
        Schema::dropIfExists('pesagens');
    }
};
