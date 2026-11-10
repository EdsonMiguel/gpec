<?php

namespace App\Providers;

use App\Modules\Agenda\Actions\SincronizarAgenda;
use App\Modules\Agenda\Models\Compromisso;
use App\Modules\Auth\Models\User;
use App\Modules\Financeiro\Models\Transacao;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Reproducao\Models\Gestacao;
use App\Modules\Sanidade\Models\ManejoSanitario;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::define('administrar', fn (User $user): bool => $user->isAdmin());
        Gate::define('registrar-manejo', fn (User $user): bool => in_array($user->role, ['Admin', 'Vaqueiro'], true));

        Relation::morphMap([
            Compromisso::APLICACAO => ManejoSanitario::class,
            Compromisso::PARTO => Gestacao::class,
            Compromisso::FINANCEIRO => Transacao::class,
        ]);
        ManejoSanitario::observe(SincronizarAgenda::class);
        Gestacao::observe(SincronizarAgenda::class);
        Transacao::observe(SincronizarAgenda::class);
        Animal::observe(SincronizarAgenda::class);

        foreach (['Auth', 'Rebanho', 'Ponderal', 'Sanidade', 'Dashboard', 'Fazendas', 'Alimentacao', 'Financeiro', 'Reproducao', 'Agenda'] as $module) {
            $this->loadRoutesFrom(app_path("Modules/{$module}/routes.php"));
            $this->loadViewsFrom(resource_path('views/modules/'.strtolower($module)), strtolower($module));
        }
    }
}
