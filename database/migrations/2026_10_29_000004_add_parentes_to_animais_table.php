<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('animais', function (Blueprint $table): void {
            $table->unsignedBigInteger('pai_id')->nullable();
            $table->unsignedBigInteger('mae_id')->nullable();
            $table->index(['tenant_id', 'pai_id']);
            $table->index(['tenant_id', 'mae_id']);
            $table->foreign(['tenant_id', 'pai_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
            $table->foreign(['tenant_id', 'mae_id'])->references(['tenant_id', 'id'])->on('animais')->restrictOnDelete();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE animais ADD CONSTRAINT animais_pai_check CHECK (pai_id IS NULL OR pai_id <> id)');
            DB::statement('ALTER TABLE animais ADD CONSTRAINT animais_mae_check CHECK (mae_id IS NULL OR mae_id <> id)');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE animais DROP CONSTRAINT animais_pai_check');
            DB::statement('ALTER TABLE animais DROP CONSTRAINT animais_mae_check');
        }

        Schema::table('animais', function (Blueprint $table): void {
            $table->dropForeign(['tenant_id', 'pai_id']);
            $table->dropForeign(['tenant_id', 'mae_id']);
            $table->dropIndex(['tenant_id', 'pai_id']);
            $table->dropIndex(['tenant_id', 'mae_id']);
            $table->dropColumn(['pai_id', 'mae_id']);
        });
    }
};
