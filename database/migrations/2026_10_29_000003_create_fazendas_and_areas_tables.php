<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fazendas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('nome', 150);
            $table->string('codigo', 50)->nullable();
            $table->string('municipio', 150)->nullable();
            $table->char('uf', 2)->nullable();
            $table->decimal('area_total_ha', 12, 2)->nullable();
            $table->text('observacao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->unique(['tenant_id', 'id']);
            $table->unique(['tenant_id', 'nome']);
            $table->unique(['tenant_id', 'codigo']);
        });

        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('fazenda_id');
            $table->string('nome', 100);
            $table->string('codigo', 50)->nullable();
            $table->enum('tipo', ['PASTO', 'PIQUETE', 'CURRAL', 'CONFINAMENTO', 'BAIA', 'RECEPCAO', 'EMBARQUE', 'OUTRO']);
            $table->decimal('area_ha', 12, 2)->nullable();
            $table->text('descricao')->nullable();
            $table->boolean('ativo')->default(true);
            $table->timestampsTz();
            $table->foreign(['tenant_id', 'fazenda_id'])->references(['tenant_id', 'id'])->on('fazendas')->restrictOnDelete();
            $table->unique(['tenant_id', 'fazenda_id', 'nome']);
            $table->unique(['tenant_id', 'fazenda_id', 'codigo']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE fazendas ADD CONSTRAINT fazendas_area_check CHECK (area_total_ha IS NULL OR area_total_ha >= 0)');
            DB::statement('ALTER TABLE areas ADD CONSTRAINT areas_area_check CHECK (area_ha IS NULL OR area_ha >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
        Schema::dropIfExists('fazendas');
    }
};
