@props(['name', 'label', 'value' => null])
<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>
    <textarea id="{{ $name }}" name="{{ $name }}" rows="3" {{ $attributes->class(['field']) }} @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>{{ old($name, $value) }}</textarea>
    @error($name)<p id="{{ $name }}-error" class="text-sm text-red-700">{{ $message }}</p>@enderror
</div>
