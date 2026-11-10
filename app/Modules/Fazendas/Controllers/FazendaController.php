<?php

namespace App\Modules\Fazendas\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Fazendas\Models\Fazenda;
use App\Modules\Fazendas\Requests\FazendaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FazendaController extends Controller
{
    public function index(): View
    {
        return view('fazendas::index', ['fazendas' => Fazenda::withCount('areas')->orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return view('fazendas::form', ['fazenda' => new Fazenda(['ativo' => true])]);
    }

    public function store(FazendaRequest $request): RedirectResponse
    {
        $fazenda = Fazenda::create($request->validatedWithGeoJson());

        return to_route('fazendas.show', $fazenda)->with('success', 'Fazenda cadastrada. Agora você pode cadastrar suas áreas.');
    }

    public function show(Fazenda $fazenda): View
    {
        $fazenda->loadCount('areas');

        return view('fazendas::show', ['fazenda' => $fazenda, 'areas' => $fazenda->areas()->orderBy('nome')->paginate(20)]);
    }

    public function edit(Fazenda $fazenda): View
    {
        return view('fazendas::form', compact('fazenda'));
    }

    public function update(FazendaRequest $request, Fazenda $fazenda): RedirectResponse
    {
        $fazenda->update($request->validatedWithGeoJson());

        return to_route('fazendas.show', $fazenda)->with('success', 'Fazenda atualizada.');
    }
}
