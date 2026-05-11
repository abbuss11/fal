<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.reset.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.reset.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.reset.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" :value="__('ui.auth.reset.email')" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('ui.auth.reset.password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('ui.auth.reset.password_confirmation')" />
            <x-text-input id="password_confirmation" class="mt-1 w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between pt-1">
            <a href="{{ route('login') }}" class="text-sm font-medium text-slate-500 transition hover:text-[var(--client-accent)]">{{ __('ui.auth.reset.back_to_login') }}</a>
            <x-primary-button>
                {{ __('ui.auth.reset.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
