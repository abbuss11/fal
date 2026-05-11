<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Parametres utilisateur</p>
                <h2 class="text-2xl font-semibold leading-tight client-heading-accent">Mon profil</h2>
                <p class="mt-1 text-sm text-slate-500">Gere tes informations, securite et cycle de vie du compte.</p>
            </div>
            <a href="{{ route('client.dashboard') }}" class="client-button-muted">Retour dashboard</a>
        </div>
    </x-slot>

    <div class="client-shell space-y-6 py-2">
        <section class="saas-hero">
            <div class="saas-hero-content grid gap-3 sm:grid-cols-3">
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Identite</p>
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->name }}</p>
                    <p class="saas-kpi-help">{{ auth()->user()->email }}</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Role</p>
                    <p class="text-sm font-semibold text-slate-900">{{ \App\Models\User::roleOptions()[auth()->user()->role] ?? auth()->user()->role }}</p>
                    <p class="saas-kpi-help">Acces plate-forme</p>
                </article>
                <article class="saas-kpi-card">
                    <p class="saas-kpi-label">Securite</p>
                    <p class="text-sm font-semibold text-slate-900">{{ auth()->user()->email_verified_at ? 'Email verifie' : 'Email non verifie' }}</p>
                    <p class="saas-kpi-help">Mets a jour ton mot de passe regulierement</p>
                </article>
            </div>
        </section>

        <div class="client-panel p-5 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="client-panel p-5 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="client-panel p-5 sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
