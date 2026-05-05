@props(['active'])

@php
$classes = ($active ?? false)
            ? 'relative inline-flex items-center text-sm font-semibold text-slate-900 after:absolute after:-bottom-[1.15rem] after:left-0 after:h-0.5 after:w-full after:rounded-full after:bg-[var(--client-accent)]'
            : 'relative inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
