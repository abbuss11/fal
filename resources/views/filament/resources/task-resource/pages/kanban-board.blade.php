<x-filament-panels::page>
    <style>
        .fal-board-wrap {
            display: grid;
            gap: 1rem;
        }
        .fal-board-hero {
            border: 1px solid rgba(148, 163, 184, 0.35);
            border-radius: 16px;
            padding: 1rem;
            background:
                radial-gradient(circle at 15% 8%, rgba(216, 96, 42, 0.16), transparent 36%),
                radial-gradient(circle at 88% 92%, rgba(15, 118, 110, 0.14), transparent 34%),
                linear-gradient(135deg, #fff 0%, #f8fafc 100%);
        }
        .fal-board-grid {
            display: grid;
            gap: 0.85rem;
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .fal-column {
            border: 1px solid rgba(148, 163, 184, 0.34);
            border-radius: 14px;
            background: #fff;
            padding: 0.8rem;
            min-height: 320px;
            transition: border-color 160ms ease, box-shadow 160ms ease;
        }
        .fal-column-active {
            border-color: rgba(217, 119, 6, 0.62);
            box-shadow: inset 0 0 0 2px rgba(217, 119, 6, 0.18);
        }
        .fal-task {
            border: 1px solid #dbe2ea;
            border-radius: 12px;
            padding: 0.7rem;
            background: #fff;
            box-shadow: 0 10px 18px -16px rgba(15, 23, 42, 0.4);
            cursor: grab;
            transition: transform 160ms ease, border-color 160ms ease;
        }
        .fal-task:hover {
            transform: translateY(-1px);
            border-color: rgba(216, 96, 42, 0.4);
        }
        .fal-task-drag {
            opacity: 0.55;
        }
        .fal-task-badge {
            border-radius: 999px;
            background: #f1f5f9;
            color: #475569;
            font-size: 0.66rem;
            font-weight: 700;
            padding: 0.22rem 0.48rem;
        }
        @media (max-width: 1200px) {
            .fal-board-grid {
                grid-template-columns: 1fr;
            }
            .fal-column {
                min-height: 220px;
            }
        }
    </style>

    <div class="fal-board-wrap" x-data="kanbanDnD()">
        <section class="fal-board-hero">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Task Command Center</p>
                    <h2 class="mt-1 text-lg font-semibold text-slate-900">
                        {{ $this->boardMode === 'scrum' ? 'Scrum Board Live' : 'Kanban Board Live' }}
                    </h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Drag/drop avec repositionnement intra-colonne + synchronisation realtime.
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <x-filament::button
                        size="sm"
                        :color="$this->boardMode === 'kanban' ? 'primary' : 'gray'"
                        wire:click="$set('boardMode', 'kanban')"
                    >
                        Mode Kanban
                    </x-filament::button>
                    <x-filament::button
                        size="sm"
                        :color="$this->boardMode === 'scrum' ? 'primary' : 'gray'"
                        wire:click="$set('boardMode', 'scrum')"
                    >
                        Mode Scrum
                    </x-filament::button>
                </div>
            </div>

            <div class="mt-4 grid gap-3 lg:grid-cols-[minmax(0,1fr)_290px]">
                <div class="rounded-xl border border-slate-200 bg-white p-3">
                    <label class="mb-1 block text-sm font-medium text-gray-700">Filtrer par projet</label>
                    <select
                        wire:model.live="projectId"
                        class="block w-full rounded-lg border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    >
                        <option value="">Tous les projets</option>
                        @foreach ($this->projects as $project)
                            <option value="{{ $project->id }}">{{ $project->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rounded-xl border border-slate-200 bg-white p-3 text-sm text-slate-600">
                    <p class="font-semibold text-slate-800">Guide rapide</p>
                    <p class="mt-1">Depose une carte dans la colonne cible et a la position souhaitee.</p>
                    <p class="mt-1">
                        @if ($this->boardMode === 'scrum')
                            `Backlog` = To Do, `Sprint en cours` = Doing, `Sprint termine` = Done.
                        @else
                            Tri par statut operationnel: To Do, Doing, Done.
                        @endif
                    </p>
                </div>
            </div>
        </section>

        <div class="fal-board-grid">
            @foreach ($this->statusLabels as $status => $label)
                <section
                    class="fal-column"
                    data-board-column="{{ $status }}"
                    @dragover.prevent="onColumnDragOver($el, $event)"
                    @dragleave.prevent="onColumnDragLeave($el)"
                    @drop.prevent="onColumnDrop($el, $event, '{{ $status }}', $wire)"
                >
                    <header class="mb-3 flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-900">{{ $label }}</h3>
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-700">
                            {{ count($this->columns[$status] ?? []) }}
                        </span>
                    </header>

                    <div class="space-y-3">
                        @forelse ($this->columns[$status] ?? [] as $task)
                            <article
                                wire:key="task-{{ $task['id'] }}"
                                data-task-id="{{ $task['id'] }}"
                                class="fal-task"
                                draggable="true"
                                @dragstart="onDragStart($el, {{ $task['id'] }})"
                                @dragend="onDragEnd($el)"
                            >
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-gray-900">{{ $task['title'] }}</p>
                                    <p class="text-xs text-gray-500">{{ $task['project'] ?? 'Projet non defini' }}</p>
                                    <p class="text-xs text-gray-500">Assigne: {{ $task['assignee'] ?? 'Non assigne' }}</p>
                                </div>
                                <div class="mt-2 flex items-center justify-between gap-2">
                                    <span class="fal-task-badge">{{ ucfirst((string) ($task['priority'] ?? 'n/a')) }}</span>
                                    <span class="text-[11px] text-gray-500">{{ $task['due_date'] ?? 'Aucune echeance' }}</span>
                                </div>
                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                    @if ($status !== \App\Models\Task::STATUS_TODO)
                                        <x-filament::button
                                            size="xs"
                                            color="gray"
                                            wire:click="shiftTask({{ $task['id'] }}, 'left')"
                                        >
                                            Reculer
                                        </x-filament::button>
                                    @endif

                                    @if ($status !== \App\Models\Task::STATUS_DONE)
                                        <x-filament::button
                                            size="xs"
                                            wire:click="shiftTask({{ $task['id'] }}, 'right')"
                                        >
                                            Avancer
                                        </x-filament::button>
                                    @endif

                                    <x-filament::button
                                        size="xs"
                                        color="gray"
                                        tag="a"
                                        href="{{ \App\Filament\Resources\TaskResource::getUrl('edit', ['record' => $task['id']]) }}"
                                    >
                                        Ouvrir
                                    </x-filament::button>
                                </div>
                            </article>
                        @empty
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-500">Aucune tache.</p>
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>

    <script>
        function kanbanDnD() {
            return {
                draggingTask: null,
                onDragStart(el, taskId) {
                    this.draggingTask = Number(taskId);
                    el.classList.add('fal-task-drag');
                },
                onDragEnd(el) {
                    this.draggingTask = null;
                    el.classList.remove('fal-task-drag');
                    document.querySelectorAll('.fal-column').forEach((col) => {
                        col.classList.remove('fal-column-active');
                    });
                },
                onColumnDragOver(column, event) {
                    event.preventDefault();
                    column.classList.add('fal-column-active');
                },
                onColumnDragLeave(column) {
                    column.classList.remove('fal-column-active');
                },
                computeDropPosition(column, event) {
                    const cards = Array.from(column.querySelectorAll('[data-task-id]'));
                    const currentTaskId = this.draggingTask;
                    const filtered = cards.filter((card) => Number(card.dataset.taskId) !== currentTaskId);

                    let position = filtered.length + 1;
                    for (let i = 0; i < filtered.length; i++) {
                        const card = filtered[i];
                        const rect = card.getBoundingClientRect();
                        const middleY = rect.top + (rect.height / 2);
                        if (event.clientY < middleY) {
                            position = i + 1;
                            break;
                        }
                    }

                    return position;
                },
                async onColumnDrop(column, event, status, wire) {
                    column.classList.remove('fal-column-active');

                    if (this.draggingTask === null) {
                        return;
                    }

                    const position = this.computeDropPosition(column, event);
                    const taskId = this.draggingTask;
                    this.draggingTask = null;

                    await wire.moveTask(taskId, status, position);
                },
            };
        }
    </script>
</x-filament-panels::page>
