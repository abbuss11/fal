<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.verify.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.verify.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.verify.subtitle') }}</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700">
            {{ __('ui.auth.verify.resent') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('ui.auth.verify.submit') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm font-semibold text-slate-500 transition hover:text-[var(--client-accent)]">
                {{ __('ui.auth.verify.logout') }}
            </button>
        </form>
    </div>
</x-guest-layout>
