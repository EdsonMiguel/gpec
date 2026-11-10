<?php

namespace App\Modules\Ponderal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Ponderal\Models\MotivoPesagem;
use App\Modules\Ponderal\Models\Pesagem;
use App\Modules\Ponderal\Requests\PesagemRequest;
use App\Modules\Rebanho\Models\Animal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PesagemController extends Controller
{
    public function index(Request $request): View
    {
        $selected = $request->filled('animal_id') ? Animal::ativos()->findOrFail($request->integer('animal_id')) : null;

        return view('ponderal::index', [
            'selected' => $selected,
            'motivos' => MotivoPesagem::where('ativo', true)->orderBy('nome')->get(),
            'pesagens' => Pesagem::with(['animal', 'motivo'])->orderByDesc('data_pesagem')->paginate(20),
        ]);
    }

    public function store(PesagemRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['data_pesagem'] = $request->date('data_pesagem');
        DB::transaction(function () use ($data): void {
            $motivo = MotivoPesagem::whereKey($data['motivo_pesagem_id'])->lockForUpdate()->first();
            if ($motivo === null || ! $motivo->ativo) {
                throw ValidationException::withMessages(['motivo_pesagem_id' => 'Selecione um motivo ativo desta conta.']);
            }
            Pesagem::create($data);
        });

        return to_route('pesagens.index')->with('success', 'Pesagem registrada. Pronto para o próximo animal.');
    }
}
