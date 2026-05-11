<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.login.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.login.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.login.subtitle') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('ui.auth.login.email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('ui.auth.login.password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-[var(--client-accent)] focus:ring-cyan-200" name="remember">
            <span>{{ __('ui.auth.login.remember') }}</span>
        </label>

        <div class="flex items-center justify-between pt-1">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-slate-500 transition hover:text-[var(--client-accent)]" href="{{ route('password.request') }}">
                    {{ __('ui.auth.login.forgot') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('ui.auth.login.submit') }}
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-5 text-sm text-slate-500">
            {{ __('ui.auth.login.new_here') }}
            <a href="{{ route('register') }}" class="font-semibold text-[var(--client-accent)] hover:underline">{{ __('ui.auth.login.create_account') }}</a>
        </p>
    @endif
</x-guest-layout>
