@props(['selected' => null, 'lotes', 'loteId' => null])
@php($porLote = old('destino', $loteId ? 'LOTE' : 'ANIMAL') === 'LOTE')

<div class="space-y-3 sm:col-span-2" data-destino-lancamento>
    <input type="hidden" name="chave_lancamento" value="{{ old('chave_lancamento', \Illuminate\Support\Str::uuid()->toString()) }}">
    <div class="grid gap-4 sm:grid-cols-2">
        <x-select name="destino" label="Registrar para *" required data-destino-select>
            <option value="ANIMAL" @selected(!$porLote)>Um animal</option>
            <option value="LOTE" @selected($porLote)>Animais de um lote</option>
        </x-select>
        <fieldset class="min-w-0" data-destino-animal @disabled($porLote) @if($porLote) hidden @endif>
            <legend class="sr-only">Animal</legend>
            <x-animal-field :selected="$selected" />
        </fieldset>
        <fieldset class="min-w-0" data-destino-lote @disabled(!$porLote) @unless($porLote) hidden @endunless>
            <legend class="sr-only">Lote</legend>
            <x-select name="lote_id" label="Lote *" required data-lote-select>
                <option value="">Selecione</option>
                @foreach($lotes as $lote)
                    <option value="{{ $lote->id }}" data-quantidade="{{ $lote->animais_count }}" @selected(old('lote_id', $loteId) == $lote->id)>{{ $lote->nome }} · {{ $lote->animais_count }} ativos</option>
                @endforeach
            </x-select>
        </fieldset>
    </div>
    <p class="rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900" data-lote-resumo aria-live="polite" @unless($porLote) hidden @endunless>
        Informe a quantidade por animal. Será criado um registro individual para cada animal ativo do lote.
    </p>
</div>
