<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="fal-brand-kicker">Espace Collaborateur</p>
                <h1 class="mt-1 text-2xl font-semibold client-heading-accent">Modifier tache</h1>
                <p class="mt-1 text-sm text-slate-500">#{{ $task->id }} - {{ $task->title }}</p>
            </div>
            <div class="flex items-center gap-2">
                @if ($task->project_id)
                    <a href="{{ route('client.projects.show', $task->project_id) }}#board" class="client-button-muted">Retour board</a>
                @else
                    <a href="{{ route('client.tasks.index') }}" class="client-button-muted">Retour taches</a>
                @endif
            </div>
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
            <form method="POST" action="{{ route('client.tasks.update', $task) }}">
                @csrf
                @method('PATCH')
                @include('client.tasks.partials.form', ['submitLabel' => 'Enregistrer les modifications'])
            </form>
        </section>

        @if (auth()->user()?->hasPermission('tasks.delete'))
            <section class="client-panel p-5">
                <h2 class="text-base font-semibold text-rose-700">Zone de suppression</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Cette action supprime definitivement la tache.
                </p>
                <form
                    method="POST"
                    action="{{ route('client.tasks.destroy', $task) }}"
                    class="mt-4"
                    onsubmit="return confirm('Supprimer cette tache ?');"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="client-button-muted !text-rose-700">Supprimer cette tache</button>
                </form>
            </section>
        @endif
    </div>
</x-app-layout>
