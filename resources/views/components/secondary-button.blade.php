<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-cyan-200 hover:text-[var(--client-accent)] focus:outline-none focus:ring-2 focus:ring-cyan-200 disabled:opacity-25']) }}>
    {{ $slot }}
</button>
