<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Modifier equipe</h1>
                <p class="mt-1 text-sm text-slate-500">Mise a jour de la structure et des membres de l'equipe.</p>
            </div>
            <a href="{{ route('client.teams.show', $team) }}" class="client-button-muted">Retour fiche equipe</a>
        </div>
    </x-slot>

    <div class="client-shell space-y-5">
        @if ($errors->any())
            <div class="client-panel border-l-4 border-l-rose-500 p-4 text-sm text-rose-700">
                <p class="font-semibold">Le formulaire contient des erreurs.</p>
                <ul class="mt-2 list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="client-panel p-5">
            <form method="POST" action="{{ route('client.teams.update', $team) }}">
                @csrf
                @method('PATCH')
                @include('client.teams.partials.form', ['submitLabel' => 'Enregistrer les changements'])
            </form>
        </section>
    </div>
</x-app-layout>
