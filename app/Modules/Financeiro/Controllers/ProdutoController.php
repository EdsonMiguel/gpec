<?php

namespace App\Modules\Financeiro\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Financeiro\Actions\FinanceiroTransaction;
use App\Modules\Financeiro\Models\Produto;
use App\Modules\Financeiro\Requests\ProdutoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProdutoController extends Controller
{
    public function __construct(private FinanceiroTransaction $transaction) {}

    public function index(): View
    {
        return view('financeiro::produtos.index', ['produtos' => Produto::withCount('itens')->orderBy('nome')->paginate(20)]);
    }

    public function create(): View
    {
        return view('financeiro::produtos.form', ['produto' => new Produto(['ativo' => true])]);
    }

    public function store(ProdutoRequest $request): RedirectResponse
    {
        $this->transaction->run(fn () => Produto::create($request->validated()));

        return to_route('produtos.index')->with('success', 'Produto cadastrado.');
    }

    public function edit(Produto $produto): View
    {
        return view('financeiro::produtos.form', compact('produto'));
    }

    public function update(ProdutoRequest $request, Produto $produto): RedirectResponse
    {
        $this->transaction->run(function () use ($request, $produto): void {
            $record = Produto::findOrFail($produto->id);
            if ($record->unidade !== $request->validated('unidade') && $record->itens()->exists()) {
                throw ValidationException::withMessages(['unidade' => 'Produto utilizado. Cadastre outro produto para uma nova unidade.']);
            }
            $record->update($request->validated());
        });

        return to_route('produtos.index')->with('success', 'Produto atualizado.');
    }

    public function destroy(Produto $produto): RedirectResponse
    {
        $this->transaction->run(function () use ($produto): void {
            $record = Produto::findOrFail($produto->id);
            if ($record->itens()->exists()) {
                throw ValidationException::withMessages(['produto' => 'Produto utilizado. Inative-o para preservar o histórico.']);
            }
            $record->delete();
        });

        return to_route('produtos.index')->with('success', 'Produto excluído.');
    }
}
