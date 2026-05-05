<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition duration-200 ease-out hover:-translate-y-0.5 hover:brightness-105 focus:outline-none focus:ring-2 focus:ring-orange-300 focus:ring-offset-2']) }} style="background: linear-gradient(135deg, #d8602a 0%, #bb3f1f 100%); box-shadow: 0 14px 24px -18px rgba(216, 96, 42, 0.45);">
    {{ $slot }}
</button>
