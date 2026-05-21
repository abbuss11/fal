@php
    $selectedTagIds = collect(old('tag_ids', $task->relationLoaded('tags') ? $task->tags->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedDependencyIds = collect(old('dependency_ids', $task->relationLoaded('dependencies') ? $task->dependencies->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedStatus = old('status', $task->status ?: \App\Models\Task::STATUS_TODO);
    $selectedAssignee = old('assigned_to', $task->assigned_to ?? '');
    $inReview = old('is_in_review', $task->is_in_review ?? false) ? true : false;
@endphp

<div class="grid gap-4 md:grid-cols-2" x-data="{ status: @js((string) $selectedStatus), inReview: @js($inReview) }">
    <div>
        <label for="project_id" class="saas-label">Projet</label>
        <select id="project_id" name="project_id" class="saas-field" required>
            @foreach ($projects as $projectOption)
                <option value="{{ $projectOption->id }}" @selected((string) old('project_id', $selectedProjectId ?? $task->project_id) === (string) $projectOption->id)>
                    {{ $projectOption->name }}
                </option>
            @endforeach
        </select>
        @error('project_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="assigned_to" class="saas-label">Assigne a</label>
        <select id="assigned_to" name="assigned_to" class="saas-field">
            <option value="">Non assigne</option>
            @foreach ($assignees as $assignee)
                <option value="{{ $assignee->id }}" @selected((string) $selectedAssignee === (string) $assignee->id)>
                    {{ $assignee->name }}
                </option>
            @endforeach
        </select>
        @error('assigned_to')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="title" class="saas-label">Titre</label>
        <input id="title" name="title" type="text" class="saas-field" value="{{ old('title', $task->title ?? '') }}" required>
        @error('title')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="description" class="saas-label">Description</label>
        <textarea id="description" name="description" rows="4" class="saas-field">{{ old('description', $task->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="saas-label">Statut</label>
        <select id="status" name="status" class="saas-field" required x-model="status">
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="priority" class="saas-label">Priorite</label>
        <select id="priority" name="priority" class="saas-field" required>
            @foreach ($priorityOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('priority', $task->priority ?: \App\Models\Task::PRIORITY_MEDIUM) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('priority')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="estimated_hours" class="saas-label">Heures estimees</label>
        <input
            id="estimated_hours"
            name="estimated_hours"
            type="number"
            min="0"
            class="saas-field"
            value="{{ old('estimated_hours', $task->estimated_hours ?? '') }}"
        >
        @error('estimated_hours')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="due_date" class="saas-label">Echeance</label>
        <input
            id="due_date"
            name="due_date"
            type="datetime-local"
            class="saas-field"
            value="{{ old('due_date', $task->due_date?->format('Y-m-d\TH:i')) }}"
        >
        @error('due_date')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="is_in_review"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                x-model="inReview"
                :disabled="status !== 'doing'"
                @checked($inReview)
            >
            <span>Marquer en revue (uniquement pour le statut "Doing")</span>
        </label>
        <p class="mt-1 text-xs text-slate-500">Si le statut n'est pas "Doing", la tache sera automatiquement hors revue.</p>
        @error('is_in_review')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="tag_ids" class="saas-label">Tags</label>
        <select id="tag_ids" name="tag_ids[]" class="saas-field" multiple size="5">
            @foreach ($tags as $tag)
                <option value="{{ $tag->id }}" @selected(in_array((string) $tag->id, $selectedTagIds, true))>
                    {{ $tag->name }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Maintiens Ctrl (Windows) pour selectionner plusieurs tags.</p>
        @error('tag_ids')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('tag_ids.*')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="dependency_ids" class="saas-label">Dependances</label>
        <select id="dependency_ids" name="dependency_ids[]" class="saas-field" multiple size="6">
            @foreach ($dependencyCandidates as $candidate)
                <option value="{{ $candidate->id }}" @selected(in_array((string) $candidate->id, $selectedDependencyIds, true))>
                    #{{ $candidate->id }} - {{ $candidate->title }}
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Liste limitee aux taches du projet selectionne.</p>
        @error('dependency_ids')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('dependency_ids.*')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center gap-2">
    <button type="submit" class="client-button">{{ $submitLabel }}</button>
    @if (isset($task) && $task->exists && $task->project_id)
        <a href="{{ route('client.projects.show', $task->project_id) }}#board" class="client-button-muted">Annuler</a>
    @else
        <a href="{{ route('client.tasks.index') }}" class="client-button-muted">Annuler</a>
    @endif
</div>
