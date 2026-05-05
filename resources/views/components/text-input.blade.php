@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border border-slate-200 bg-white/90 px-3 py-2.5 text-sm text-slate-900 shadow-sm transition focus:border-orange-300 focus:ring-orange-200']) }}>
