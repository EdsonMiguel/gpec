<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compromissos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->restrictOnDelete();
            $table->string('origem_type', 30);
            $table->unsignedBigInteger('origem_id');
            $table->string('titulo', 255);
            $table->date('data_prevista');
            $table->enum('status', ['PENDENTE', 'CONCLUIDO', 'CANCELADO'])->default('PENDENTE');
            $table->timestampsTz();
            $table->softDeletesTz();
            $table->unique(['tenant_id', 'origem_type', 'origem_id']);
            $table->index(['tenant_id', 'status', 'data_prevista']);
        });

        $colunas = ['tenant_id', 'origem_type', 'origem_id', 'titulo', 'data_prevista', 'status', 'created_at', 'updated_at'];
        $mysql = DB::getDriverName() === 'mysql';
        $tituloManejo = $mysql
            ? "CONCAT('Brinco ', animal.brinco, ' · ', medicamento.nome)"
            : "'Brinco ' || animal.brinco || ' · ' || medicamento.nome";
        $tituloParto = $mysql ? "CONCAT('Parto da matriz ', matriz.brinco)" : "'Parto da matriz ' || matriz.brinco";
        $prefixoFinanceiro = "CASE WHEN tipo = 'COMPRA' THEN 'Pagamento · ' ELSE 'Recebimento · ' END";
        $tituloFinanceiro = $mysql ? "CONCAT(($prefixoFinanceiro), descricao)" : "($prefixoFinanceiro) || descricao";

        DB::table('compromissos')->insertUsing($colunas, DB::table('manejos_sanitarios as manejo')
            ->join('animais as animal', 'animal.id', '=', 'manejo.animal_id')
            ->join('medicamentos as medicamento', 'medicamento.id', '=', 'manejo.medicamento_id')
            ->where('animal.status', 'ATIVO')->whereNull('animal.deleted_at')->whereNotNull('manejo.proxima_aplicacao')
            ->whereNotExists(function (Builder $posterior): void {
                $posterior->selectRaw('1')->from('manejos_sanitarios as posterior')
                    ->whereColumn('posterior.tenant_id', 'manejo.tenant_id')
                    ->whereColumn('posterior.animal_id', 'manejo.animal_id')
                    ->whereColumn('posterior.medicamento_id', 'manejo.medicamento_id')
                    ->where(function (Builder $data): void {
                        $data->whereColumn('posterior.data_aplicacao', '>', 'manejo.data_aplicacao')
                            ->orWhere(function (Builder $mesmoDia): void {
                                $mesmoDia->whereColumn('posterior.data_aplicacao', 'manejo.data_aplicacao')
                                    ->whereColumn('posterior.id', '>', 'manejo.id');
                            });
                    });
            })
            ->selectRaw("manejo.tenant_id, 'manejo_sanitario', manejo.id, $tituloManejo,
                manejo.proxima_aplicacao, 'PENDENTE', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP"));

        DB::table('compromissos')->insertUsing($colunas, DB::table('gestacoes as gestacao')
            ->join('animais as matriz', 'matriz.id', '=', 'gestacao.matriz_id')
            ->where('gestacao.status', 'CONFIRMADA')->whereNotNull('gestacao.data_prevista_parto')
            ->where('matriz.status', 'ATIVO')->whereNull('matriz.deleted_at')
            ->selectRaw("gestacao.tenant_id, 'gestacao', gestacao.id, $tituloParto,
                gestacao.data_prevista_parto, 'PENDENTE', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP"));

        DB::table('compromissos')->insertUsing($colunas, DB::table('transacoes')
            ->where('status', 'PENDENTE')->whereNotNull('data_vencimento')
            ->selectRaw("tenant_id, 'transacao', id,
                substr($tituloFinanceiro, 1, 255),
                data_vencimento, 'PENDENTE', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP"));
    }

    public function down(): void
    {
        Schema::dropIfExists('compromissos');
    }
};
