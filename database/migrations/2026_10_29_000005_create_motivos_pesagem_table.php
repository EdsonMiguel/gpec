<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('motivos_pesagem', function (Blueprint $table): void {
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

        Schema::table('pesagens', function (Blueprint $table): void {
            $table->unsignedBigInteger('motivo_pesagem_id')->nullable();
        });

        foreach (DB::table('pesagens')->select('tenant_id')->distinct()->pluck('tenant_id') as $tenantId) {
            $motivoId = DB::table('motivos_pesagem')->insertGetId([
                'tenant_id' => $tenantId, 'codigo' => 'LEGADO', 'nome' => 'Não informado — registro anterior',
                'descricao' => 'Motivo não registrado nas pesagens anteriores à implantação deste cadastro.',
                'ativo' => false, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('pesagens')->where('tenant_id', $tenantId)->update(['motivo_pesagem_id' => $motivoId]);
        }

        Schema::table('pesagens', function (Blueprint $table): void {
            $table->unsignedBigInteger('motivo_pesagem_id')->nullable(false)->change();
            $table->index(['tenant_id', 'motivo_pesagem_id']);
            $table->foreign(['tenant_id', 'motivo_pesagem_id'])->references(['tenant_id', 'id'])->on('motivos_pesagem')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pesagens', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id', 'motivo_pesagem_id']);
            $table->dropIndex(['tenant_id', 'motivo_pesagem_id']);
            $table->dropColumn('motivo_pesagem_id');
        });
        Schema::dropIfExists('motivos_pesagem');
    }
};
