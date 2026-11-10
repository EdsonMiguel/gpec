@props(['selected' => null])
<x-field name="brinco" label="Brinco do animal" :value="$selected?->brinco" required maxlength="50" autocomplete="off" autofocus hint="Digite o brinco completo de um animal ativo da fazenda." />
@error('animal_id')<p class="text-sm text-red-700">Animal não encontrado. Confira o brinco e se o animal está ativo.</p>@enderror
