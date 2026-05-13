@php
    $isTemplateChecked = old('is_template', $project->is_template ?? false) ? true : false;
@endphp

<div class="grid gap-4 md:grid-cols-2" x-data="{ isTemplate: @js($isTemplateChecked) }">
    <div class="md:col-span-2">
        <label for="name" class="saas-label">Nom du projet</label>
        <input
            id="name"
            name="name"
            type="text"
            class="saas-field"
            value="{{ old('name', $project->name ?? '') }}"
            required
        >
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="client_id" class="saas-label">Client</label>
        <select id="client_id" name="client_id" class="saas-field">
            <option value="">Interne</option>
            @foreach ($clients as $client)
                <option value="{{ $client->id }}" @selected((string) old('client_id', $project->client_id ?? '') === (string) $client->id)>
                    {{ $client->name }}
                </option>
            @endforeach
        </select>
        @error('client_id')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="status" class="saas-label">Statut</label>
        <select id="status" name="status" class="saas-field" required>
            @foreach ($statusOptions as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $project->status ?: \App\Models\Project::STATUS_PLANNING) === $value)>
                    {{ $label }}
                </option>
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
                <option value="{{ $value }}" @selected(old('priority', $project->priority ?: \App\Models\Project::PRIORITY_MEDIUM) === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('priority')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="budget" class="saas-label">Budget</label>
        <input
            id="budget"
            name="budget"
            type="number"
            min="0"
            step="0.01"
            class="saas-field"
            value="{{ old('budget', $project->budget ?? '') }}"
            placeholder="0.00"
        >
        @error('budget')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="start_date" class="saas-label">Date debut</label>
        <input
            id="start_date"
            name="start_date"
            type="date"
            class="saas-field"
            value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"
        >
        @error('start_date')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="due_date" class="saas-label">Date echeance</label>
        <input
            id="due_date"
            name="due_date"
            type="date"
            class="saas-field"
            value="{{ old('due_date', optional($project->due_date)->format('Y-m-d')) }}"
        >
        @error('due_date')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="is_template"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                x-model="isTemplate"
                @checked($isTemplateChecked)
            >
            <span>Marquer comme template</span>
        </label>
    </div>

    <div class="md:col-span-2" x-show="isTemplate" x-cloak>
        <label for="template_name" class="saas-label">Nom du template</label>
        <input
            id="template_name"
            name="template_name"
            type="text"
            class="saas-field"
            value="{{ old('template_name', $project->template_name ?? '') }}"
            placeholder="Nom du template"
        >
        @error('template_name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="description" class="saas-label">Description</label>
        <textarea id="description" name="description" rows="4" class="saas-field">{{ old('description', $project->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="objective" class="saas-label">Objectif</label>
        <textarea id="objective" name="objective" rows="3" class="saas-field">{{ old('objective', $project->objective ?? '') }}</textarea>
        @error('objective')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center gap-2">
    <button type="submit" class="client-button">{{ $submitLabel }}</button>
    @if (isset($project) && $project->exists)
        <a href="{{ route('client.projects.show', $project) }}" class="client-button-muted">Annuler</a>
    @else
        <a href="{{ route('client.projects.index') }}" class="client-button-muted">Annuler</a>
    @endif
</div>
