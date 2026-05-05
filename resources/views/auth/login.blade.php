<x-guest-layout>
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Connexion client</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">Acceder a votre espace</h1>
        <p class="mt-2 text-sm text-slate-600">Suivez vos dossiers, echanges et validations depuis un tableau de bord unique.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2 text-sm text-slate-600">
            <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-orange-600 focus:ring-orange-300" name="remember">
            <span>{{ __('Remember me') }}</span>
        </label>

        <div class="flex items-center justify-between pt-1">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-slate-500 transition hover:text-[var(--client-accent)]" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button>
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
