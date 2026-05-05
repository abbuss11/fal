@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full rounded-xl border border-orange-200 bg-orange-50 px-3 py-2 text-start text-sm font-semibold text-[var(--client-accent)] transition'
            : 'block w-full rounded-xl border border-transparent px-3 py-2 text-start text-sm font-medium text-slate-600 transition hover:border-[var(--client-line)] hover:bg-slate-50 hover:text-slate-900';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
