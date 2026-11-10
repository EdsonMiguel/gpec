<x-layout title="Registrar sintomas">
    <x-page-heading title="Registrar sintomas" eyebrow="PRONTUÁRIO">
        <a href="{{ route('casos-clinicos.index', $selected ? ['animal_id' => $selected->id] : []) }}" class="btn-secondary">Voltar</a>
    </x-page-heading>
    <form method="POST" action="{{ route('casos-clinicos.store') }}" class="card space-y-5">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <x-animal-field :selected="$selected" />
            <x-field name="data_inicio" label="Início dos sintomas *" type="date" :value="today()->toDateString()" :max="today()->toDateString()" required />
        </div>
        <x-field name="titulo" label="Título do caso *" maxlength="150" required />
        <x-textarea name="sintomas_iniciais" label="Sintomas observados *" maxlength="5000" required />
        <p class="text-sm text-stone-600">Depois de abrir o caso, registre as avaliações e orientações do veterinário no prontuário.</p>
        <button type="submit" class="btn">Abrir caso clínico</button>
    </form>
</x-layout>
