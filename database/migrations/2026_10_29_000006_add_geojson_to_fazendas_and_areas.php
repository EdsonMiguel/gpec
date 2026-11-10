<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['fazendas', 'areas'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->json('geojson')->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['areas', 'fazendas'] as $name) {
            Schema::table($name, function (Blueprint $table): void {
                $table->dropColumn('geojson');
            });
        }
    }
};
