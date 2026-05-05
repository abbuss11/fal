<x-filament-panels::page>
    <div class="space-y-5" wire:poll.20s>
        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Mois</label>
                <input
                    type="month"
                    wire:model.live="month"
                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                />
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">Projet</label>
                <select
                    wire:model.live="projectId"
                    class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-gray-900"
                >
                    <option value="">Tous les projets</option>
                    @foreach ($this->projects as $project)
                        <option value="{{ $project->id }}">{{ $project->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-7">
            @foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $dayName)
                <div class="hidden rounded-lg border border-gray-200 bg-gray-50 py-2 text-center text-xs font-semibold uppercase tracking-wide text-gray-500 lg:block dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                    {{ $dayName }}
                </div>
            @endforeach

            @foreach ($this->calendarDays as $day)
                <article class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm {{ $day['is_current_month'] ? '' : 'opacity-45' }} dark:border-white/10 dark:bg-gray-900">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $day['date']->format('d/m') }}</p>
                    <div class="mt-2 space-y-2">
                        @forelse ($day['tasks'] as $task)
                            <a href="{{ \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task->id]) }}" class="block rounded-lg border border-gray-200 px-2 py-1 text-xs dark:border-white/10">
                                <span class="font-semibold">{{ $task->title }}</span><br>
                                <span class="text-gray-500">{{ $task->project?->name }}</span>
                            </a>
                        @empty
                            <p class="text-xs text-gray-400 dark:text-gray-500">Aucune tache</p>
                        @endforelse
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>

