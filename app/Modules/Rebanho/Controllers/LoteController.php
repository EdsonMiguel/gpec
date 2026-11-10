<?php

namespace App\Modules\Rebanho\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Rebanho\Models\Lote;
use App\Modules\Rebanho\Requests\LoteRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LoteController extends Controller
{
    public function index(): View
    {
        return view('rebanho::lotes.index', ['lotes' => Lote::withCount('animais')->orderBy('nome')->paginate(20)]);
    }

    public function store(LoteRequest $request): RedirectResponse
    {
        Lote::create($request->validated());

        return to_route('lotes.index')->with('success', 'Lote cadastrado.');
    }

    public function create(): View
    {
        return view('rebanho::lotes.form', ['lote' => new Lote]);
    }

    public function edit(Lote $lote): View
    {
        return view('rebanho::lotes.form', compact('lote'));
    }

    public function update(LoteRequest $request, Lote $lote): RedirectResponse
    {
        $lote->update($request->validated());

        return to_route('lotes.index')->with('success', 'Lote atualizado.');
    }
}
