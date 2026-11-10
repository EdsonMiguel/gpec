<?php

namespace App\Modules\Fazendas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fazendas\Models\Area;
use App\Modules\Fazendas\Models\Fazenda;
use App\Modules\Fazendas\Requests\AreaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AreaController extends Controller
{
    public function create(Fazenda $fazenda): View
    {
        return view('fazendas::areas.form', ['fazenda' => $fazenda, 'area' => new Area(['ativo' => true])]);
    }

    public function store(AreaRequest $request, Fazenda $fazenda): RedirectResponse
    {
        $fazenda->areas()->create($request->validatedWithGeoJson());

        return to_route('fazendas.show', $fazenda)->with('success', 'Área cadastrada nesta fazenda.');
    }

    public function edit(Fazenda $fazenda, Area $area): View
    {
        return view('fazendas::areas.form', compact('fazenda', 'area'));
    }

    public function update(AreaRequest $request, Fazenda $fazenda, Area $area): RedirectResponse
    {
        $area->update($request->validatedWithGeoJson());

        return to_route('fazendas.show', $fazenda)->with('success', 'Área atualizada.');
    }
}
