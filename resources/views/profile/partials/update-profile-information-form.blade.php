<section>
    <header>
        <h2 class="text-lg font-semibold text-slate-900">
            Informations de profil
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            Mets a jour ton identite, ton email et tes informations de contact.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nom complet" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div>
                    <p class="mt-2 text-sm text-slate-700">
                        Votre adresse email n'est pas verifiee.

                        <button form="send-verification" class="rounded-md text-sm font-medium text-[var(--client-accent)] hover:text-cyan-700 focus:outline-none focus:ring-2 focus:ring-cyan-200">
                            Cliquez ici pour renvoyer le lien de verification.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-700">
                            Un nouveau lien de verification a ete envoye.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="job_title" value="Poste" />
            <x-text-input id="job_title" name="job_title" type="text" class="mt-1 block w-full" :value="old('job_title', $user->job_title)" autocomplete="organization-title" />
            <x-input-error class="mt-2" :messages="$errors->get('job_title')" />
        </div>

        <div>
            <x-input-label for="phone" value="Telephone" />
            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $user->phone)" autocomplete="tel" />
            <x-input-error class="mt-2" :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="bio" value="Bio" />
            <textarea id="bio" name="bio" rows="4" class="saas-field mt-1 block w-full">{{ old('bio', $user->bio) }}</textarea>
            <x-input-error class="mt-2" :messages="$errors->get('bio')" />
        </div>

        <div class="space-y-3 rounded-xl border border-[var(--client-line)] bg-slate-50 p-4">
            <p class="text-sm font-semibold text-slate-800">Preferences de notifications</p>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="notify_email" value="0">
                <input type="checkbox" name="notify_email" value="1" @checked(old('notify_email', $user->notify_email ?? true)) class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Email
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="notify_realtime" value="0">
                <input type="checkbox" name="notify_realtime" value="1" @checked(old('notify_realtime', $user->notify_realtime ?? true)) class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Centre de notifications temps reel
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="notify_push" value="0">
                <input type="checkbox" name="notify_push" value="1" @checked(old('notify_push', $user->notify_push ?? true)) class="rounded border-slate-300 text-cyan-600 focus:ring-cyan-500">
                Push mobile
            </label>
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Enregistrer</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-slate-500"
                >Enregistre.</p>
            @endif
        </div>
    </form>
</section>
