@props(['name', 'label', 'type' => 'text', 'value' => null, 'hint' => null])
<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ old($name, $value) }}" {{ $attributes->class(['field']) }} @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @if($hint)<p class="text-xs text-stone-500">{{ $hint }}</p>@endif
    @error($name)<p id="{{ $name }}-error" class="text-sm text-red-700">{{ $message }}</p>@enderror
</div>
