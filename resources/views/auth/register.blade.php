<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.register.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.register.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.register.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" :value="__('ui.auth.register.name')" />
            <x-text-input id="name" class="mt-1" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('ui.auth.register.email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('ui.auth.register.password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('ui.auth.register.password_confirmation')" />
            <x-text-input id="password_confirmation" class="mt-1" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-2">
            <a class="text-sm font-medium text-slate-500 transition hover:text-[var(--client-accent)]" href="{{ route('login') }}">
                {{ __('ui.auth.register.already_registered') }}
            </a>

            <x-primary-button>
                {{ __('ui.auth.register.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
