@props(['name', 'label'])
<div class="space-y-1.5">
    <label for="{{ $name }}" class="block text-sm font-semibold">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}" {{ $attributes->class(['field']) }} @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>{{ $slot }}</select>
    @error($name)<p id="{{ $name }}-error" class="text-sm text-red-700">{{ $message }}</p>@enderror
</div>
