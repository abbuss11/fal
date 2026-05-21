<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.forgot.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.forgot.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.forgot.subtitle') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('ui.auth.forgot.email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 transition hover:text-[var(--client-accent)]">{{ __('ui.auth.forgot.back_to_login') }}</a>
            <x-primary-button>
                {{ __('ui.auth.forgot.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
