@props(['variant' => 'primary', 'type' => 'button'])
<button type="{{ $type }}" {{ $attributes->class([
    'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold transition disabled:cursor-wait disabled:opacity-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-teal-600',
    'bg-teal-700 text-white hover:bg-teal-800' => $variant === 'primary',
    'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' => $variant === 'secondary',
    'bg-red-50 text-red-700 hover:bg-red-100' => $variant === 'danger',
]) }}>{{ $slot }}</button>
