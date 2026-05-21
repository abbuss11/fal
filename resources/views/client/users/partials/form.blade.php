@php
    $editing = isset($userModel) && $userModel->exists;
    $selectedRole = old('role', $userModel->role ?? \App\Models\User::ROLE_MEMBER);
    $activeChecked = old('is_active', $userModel->is_active ?? true) ? true : false;
@endphp

<div class="grid gap-4 md:grid-cols-2">
    <div>
        <label for="name" class="saas-label">Nom complet</label>
        <input id="name" name="name" type="text" class="saas-field" value="{{ old('name', $userModel->name ?? '') }}" required>
        @error('name')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="email" class="saas-label">Email</label>
        <input id="email" name="email" type="email" class="saas-field" value="{{ old('email', $userModel->email ?? '') }}" required>
        @error('email')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="role" class="saas-label">Role</label>
        <select id="role" name="role" class="saas-field" required>
            @foreach ($roleOptions as $roleValue => $roleLabel)
                @if (auth()->user()->isAdmin() || $roleValue !== \App\Models\User::ROLE_ADMIN)
                    <option value="{{ $roleValue }}" @selected($selectedRole === $roleValue)>{{ $roleLabel }}</option>
                @endif
            @endforeach
        </select>
        @error('role')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="job_title" class="saas-label">Poste</label>
        <input id="job_title" name="job_title" type="text" class="saas-field" value="{{ old('job_title', $userModel->job_title ?? '') }}">
        @error('job_title')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="phone" class="saas-label">Telephone</label>
        <input id="phone" name="phone" type="text" class="saas-field" value="{{ old('phone', $userModel->phone ?? '') }}">
        @error('phone')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="saas-label">{{ $editing ? 'Nouveau mot de passe (optionnel)' : 'Mot de passe' }}</label>
        <input id="password" name="password" type="password" class="saas-field" {{ $editing ? '' : 'required' }}>
        @error('password')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="saas-label">Confirmation mot de passe</label>
        <input id="password_confirmation" name="password_confirmation" type="password" class="saas-field" {{ $editing ? '' : 'required' }}>
    </div>

    <div class="md:col-span-2">
        <label for="bio" class="saas-label">Bio</label>
        <textarea id="bio" name="bio" rows="3" class="saas-field">{{ old('bio', $userModel->bio ?? '') }}</textarea>
        @error('bio')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="md:col-span-2 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="is_active"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                @checked($activeChecked)
            >
            <span>Compte actif</span>
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="notify_email"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                @checked(old('notify_email', $userModel->notify_email ?? true))
            >
            <span>Notif email</span>
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="notify_realtime"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                @checked(old('notify_realtime', $userModel->notify_realtime ?? true))
            >
            <span>Notif live</span>
        </label>

        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
            <input
                type="checkbox"
                name="notify_push"
                value="1"
                class="rounded border-[var(--client-line)] text-[var(--client-accent)] focus:ring-[var(--client-accent)]"
                @checked(old('notify_push', $userModel->notify_push ?? true))
            >
            <span>Notif mobile</span>
        </label>
    </div>
</div>

<div class="mt-5 flex flex-wrap items-center gap-2">
    <button type="submit" class="client-button">{{ $submitLabel }}</button>
    <a href="{{ route('client.users.index') }}" class="client-button-muted">Annuler</a>
</div>
