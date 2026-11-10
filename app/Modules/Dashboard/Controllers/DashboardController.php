<?php

namespace App\Modules\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Agenda\Models\Compromisso;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Rebanho\Models\Animal;
use App\Modules\Rebanho\Models\Lote;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $pendentes = Compromisso::visiveis($request->user())->pendentes()->whereDate('data_prevista', '<=', today()->addDays(7));

        return view('dashboard::index', [
            'total' => Animal::ativos()->count(),
            'lotes' => Lote::count(),
            'categorias' => Animal::ativos()->selectRaw('categoria, COUNT(*) as total')->groupBy('categoria')->orderBy('categoria')->get(),
            'compromissosTotal' => (clone $pendentes)->count(),
            'compromissos' => $pendentes->orderBy('data_prevista')->orderBy('id')->limit(8)->get(),
            'pesagens' => Pesagem::with('animal')->orderByDesc('data_pesagem')->limit(5)->get(),
        ]);
    }
}
