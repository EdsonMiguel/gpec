<?php

namespace App\Modules\Sanidade\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sanidade\Models\Doenca;
use App\Modules\Sanidade\Requests\DoencaRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DoencaController extends Controller
{
    public function index(): View
    {
        return view('sanidade::doencas.index', ['doencas' => Doenca::orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return view('sanidade::doencas.form', ['doenca' => new Doenca(['ativo' => true])]);
    }

    public function store(DoencaRequest $request): RedirectResponse
    {
        Doenca::create($request->validated());

        return to_route('doencas.index')->with('success', 'Doença cadastrada.');
    }

    public function edit(Doenca $doenca): View
    {
        return view('sanidade::doencas.form', compact('doenca'));
    }

    public function update(DoencaRequest $request, Doenca $doenca): RedirectResponse
    {
        $doenca->update($request->validated());

        return to_route('doencas.index')->with('success', 'Cadastro da doença atualizado.');
    }
}
