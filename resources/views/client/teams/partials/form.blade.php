@php
    $editing = isset($team) && $team->exists;
    $selectedMemberIds = collect(old('member_ids', $editing ? $team->members->pluck('id')->all() : []))
        ->map(fn ($id) => (string) $id)
        ->all();
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="name" class="saas-label">Nom de l'equipe</label>
        <input id="name" name="name" type="text" class="saas-field" value="{{ old('name', $team->name ?? '') }}" required>
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="saas-label">Statut</label>
        <label class="inline-flex items-center gap-2 rounded-xl border border-[var(--client-line)] bg-white px-3 py-2.5 text-sm text-slate-700">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                @checked(old('is_active', $team->is_active ?? true))
            >
            <span>Equipe active</span>
        </label>
        @error('is_active')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    @if ($isAdmin)
        <div class="md:col-span-2">
            <label for="owner_id" class="saas-label">Proprietaire</label>
            <select id="owner_id" name="owner_id" class="saas-field">
                @foreach ($ownerOptions as $ownerOption)
                    <option value="{{ $ownerOption->id }}" @selected((string) old('owner_id', $team->owner_id ?? auth()->id()) === (string) $ownerOption->id)>
                        {{ $ownerOption->name }}
                    </option>
                @endforeach
            </select>
            @error('owner_id')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    @endif

    <div class="md:col-span-2">
        <label for="description" class="saas-label">Description</label>
        <textarea id="description" name="description" rows="3" class="saas-field">{{ old('description', $team->description ?? '') }}</textarea>
        @error('description')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2">
        <label for="member_ids" class="saas-label">Membres (selection multiple)</label>
        <select id="member_ids" name="member_ids[]" class="saas-field" multiple size="8">
            @foreach ($members as $memberOption)
                <option value="{{ $memberOption->id }}" @selected(in_array((string) $memberOption->id, $selectedMemberIds, true))>
                    {{ $memberOption->name }} ({{ $memberOption->email }})
                </option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-slate-500">Maintiens Ctrl (Windows) pour selectionner plusieurs membres.</p>
        @error('member_ids')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
        @error('member_ids.*')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center gap-2">
    <button type="submit" class="client-button">{{ $submitLabel }}</button>
    @if ($editing)
        <a href="{{ route('client.teams.show', $team) }}" class="client-button-muted">Annuler</a>
    @else
        <a href="{{ route('client.teams.index') }}" class="client-button-muted">Annuler</a>
    @endif
</div>
