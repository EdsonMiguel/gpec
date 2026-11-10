<?php

namespace App\Modules\Sanidade\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Sanidade\Models\Medicamento;
use App\Modules\Sanidade\Requests\MedicamentoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class MedicamentoController extends Controller
{
    public function index(): View
    {
        return view('sanidade::medicamentos', ['medicamentos' => Medicamento::orderBy('nome')->paginate(20)]);
    }

    public function store(MedicamentoRequest $request): RedirectResponse
    {
        Medicamento::create($request->validated());

        return to_route('medicamentos.index')->with('success', 'Medicamento cadastrado.');
    }

    public function toggle(Medicamento $medicamento): RedirectResponse
    {
        $medicamento->update(['ativo' => ! $medicamento->ativo]);

        return to_route('medicamentos.index')->with('success', 'Disponibilidade atualizada.');
    }
}
