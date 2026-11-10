<?php

namespace App\Modules\Ponderal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Requests\MotivoPesagemRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MotivoPesagemController extends Controller
{
    public function index(): View
    {
        return view('ponderal::motivos.index', ['motivos' => MotivoPesagem::withCount('pesagens')->orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return view('ponderal::motivos.form', ['motivo' => new MotivoPesagem(['ativo' => true])]);
    }

    public function store(MotivoPesagemRequest $request): RedirectResponse
    {
        $motivo = MotivoPesagem::create($request->validated());

        return to_route('motivos-pesagem.show', $motivo)->with('success', 'Motivo de pesagem cadastrado.');
    }

    public function show(MotivoPesagem $motivo): View
    {
        $motivo->loadCount('pesagens');

        return view('ponderal::motivos.show', compact('motivo'));
    }

    public function edit(MotivoPesagem $motivo): View
    {
        return view('ponderal::motivos.form', compact('motivo'));
    }

    public function update(MotivoPesagemRequest $request, MotivoPesagem $motivo): RedirectResponse
    {
        DB::transaction(function () use ($request, $motivo): void {
            MotivoPesagem::whereKey($motivo->id)->lockForUpdate()->firstOrFail()->update($request->validated());
        });

        return to_route('motivos-pesagem.show', $motivo)->with('success', 'Motivo de pesagem atualizado.');
    }

    public function destroy(MotivoPesagem $motivo): RedirectResponse
    {
        DB::transaction(function () use ($motivo): void {
            $record = MotivoPesagem::whereKey($motivo->id)->lockForUpdate()->firstOrFail();
            if ($record->pesagens()->exists()) {
                throw ValidationException::withMessages(['motivo' => 'Este motivo já foi utilizado. Inative-o para preservar o histórico das pesagens.']);
            }
            $record->delete();
        });

        return to_route('motivos-pesagem.index')->with('success', 'Motivo de pesagem excluído.');
    }
}
