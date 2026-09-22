@props(['name', 'label'])
<div>
    <label for="{{ $name }}" class="mb-2 block text-sm font-medium">{{ $label }}</label>
    {{ $slot }}
    @error($name)<p role="alert" class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
</div>
