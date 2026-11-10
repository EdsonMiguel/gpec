<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['refeicoes', 'manejos_sanitarios'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->uuid('chave_lancamento')->nullable();
                $table->string('hash_lancamento', 64)->nullable();
                $table->unique(['tenant_id', 'chave_lancamento', 'animal_id']);
            });
        }
    }

    public function down(): void
    {
        foreach (['refeicoes', 'manejos_sanitarios'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['tenant_id', 'chave_lancamento', 'animal_id']);
                $table->dropColumn(['chave_lancamento', 'hash_lancamento']);
            });
        }
    }
};
