@props(['alimentos', 'index', 'item' => []])
@php
    $item = is_array($item) ? $item : [];
    $selected = is_scalar($item['alimento_id'] ?? null) ? (string) $item['alimento_id'] : '';
    $quantity = is_scalar($item['quantidade'] ?? null) ? $item['quantidade'] : '';
@endphp
<div data-refeicao-item class="min-w-0 space-y-3 rounded-xl border border-stone-200 bg-stone-50 p-4">
    <div><label class="mb-1.5 block text-sm font-semibold" for="alimento-{{ $index }}">Alimento *</label><select id="alimento-{{ $index }}" name="itens[{{ $index }}][alimento_id]" data-item-alimento class="field" required><option value="">Selecione</option>@foreach($alimentos as $alimento)<option value="{{ $alimento->id }}" @selected($selected === (string) $alimento->id)>{{ $alimento->nome }} ({{ $alimento->unidade }})</option>@endforeach</select></div>
    <div><label class="mb-1.5 block text-sm font-semibold" for="quantidade-{{ $index }}">Quantidade por animal *</label><input id="quantidade-{{ $index }}" name="itens[{{ $index }}][quantidade]" data-item-quantidade class="field" value="{{ $quantity }}" type="number" min="0.001" max="999999999.999" step="0.001" inputmode="decimal" required><p class="mt-1 text-xs text-stone-500">Total fornecido a cada animal, na unidade exibida no alimento.</p></div>
    <button type="button" class="btn-secondary" data-remove-refeicao-item>Remover alimento</button>
</div>
