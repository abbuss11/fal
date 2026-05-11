<x-guest-layout>
    <div class="mb-6">
        <p class="fal-brand-kicker">{{ __('ui.auth.confirm.kicker') }}</p>
        <h1 class="mt-2 text-2xl font-semibold text-slate-900">{{ __('ui.auth.confirm.title') }}</h1>
        <p class="mt-2 text-sm text-slate-600">{{ __('ui.auth.confirm.subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" :value="__('ui.auth.confirm.password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex justify-end pt-1">
            <x-primary-button>
                {{ __('ui.auth.confirm.submit') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
