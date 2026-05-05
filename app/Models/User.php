<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_PROJECT_MANAGER = 'project_manager';

    public const ROLE_MEMBER = 'member';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'role',
        'is_active',
        'last_seen_at',
        'job_title',
        'phone',
        'bio',
        'avatar_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'role' => 'string',
            'is_active' => 'boolean',
            'last_seen_at' => 'datetime',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    public static function roleOptions(): array
    {
        return [
            self::ROLE_ADMIN => 'Administrateur',
            self::ROLE_PROJECT_MANAGER => 'Chef de projet',
            self::ROLE_MEMBER => 'Membre',
        ];
    }

    /**
     * @return array<string, array<int, string>>
     */
    public static function defaultRolePermissions(): array
    {
        return [
            self::ROLE_ADMIN => ['*'],
            self::ROLE_PROJECT_MANAGER => [
                'projects.read',
                'projects.create',
                'projects.update',
                'projects.manage_members',
                'tasks.read',
                'tasks.create',
                'tasks.update',
                'tasks.move',
                'tasks.subtasks.manage',
                'comments.read',
                'comments.create',
                'messages.read',
                'messages.create',
                'files.read',
                'files.create',
                'timesheets.read',
                'timesheets.create',
                'timesheets.update',
                'dashboard.read',
                'notifications.read',
            ],
            self::ROLE_MEMBER => [
                'projects.read',
                'tasks.read',
                'tasks.update',
                'tasks.move',
                'tasks.subtasks.manage',
                'comments.read',
                'comments.create',
                'messages.read',
                'messages.create',
                'files.read',
                'files.create',
                'timesheets.read',
                'timesheets.create',
                'dashboard.read',
                'notifications.read',
            ],
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN || $this->is_admin;
    }

    public function hasPermission(string $permissionCode): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $rolePermissions = self::defaultRolePermissions()[$this->role] ?? [];

        if (in_array('*', $rolePermissions, true) || in_array($permissionCode, $rolePermissions, true)) {
            return true;
        }

        $resourceWildcard = explode('.', $permissionCode)[0].'.*';
        if (in_array($resourceWildcard, $rolePermissions, true)) {
            return true;
        }

        return $this->permissions()
            ->where('code', $permissionCode)
            ->exists();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function allGrantedPermissions(): Collection
    {
        if ($this->isAdmin()) {
            return Permission::query()->get();
        }

        $rolePermissions = collect(self::defaultRolePermissions()[$this->role] ?? []);
        $directPermissions = $this->permissions()->pluck('code');

        return Permission::query()
            ->whereIn('code', $rolePermissions->merge($directPermissions)->unique()->values())
            ->get();
    }

    public function visibleProjectsQuery(): Builder
    {
        $query = Project::query();

        if ($this->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder): void {
            $builder
                ->where('owner_id', $this->id)
                ->orWhereHas('members', function (Builder $memberQuery): void {
                    $memberQuery
                        ->where('users.id', $this->id)
                        ->where('project_user.is_active', true);
                });
        });
    }

    public function visibleTasksQuery(): Builder
    {
        $query = Task::query();

        if ($this->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $builder): void {
            $builder
                ->where('assigned_to', $this->id)
                ->orWhereHas('project', function (Builder $projectQuery): void {
                    $projectQuery
                        ->where('owner_id', $this->id)
                        ->orWhereHas('members', function (Builder $memberQuery): void {
                            $memberQuery
                                ->where('users.id', $this->id)
                                ->where('project_user.is_active', true);
                        });
                });
        });
    }

    public function canAccessProject(Project $project): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($project->owner_id === $this->id) {
            return true;
        }

        return $project->members()
            ->where('users.id', $this->id)
            ->wherePivot('is_active', true)
            ->exists();
    }

    public function canManageProject(Project $project): bool
    {
        if ($this->isAdmin() || $project->owner_id === $this->id) {
            return true;
        }

        return $project->members()
            ->where('users.id', $this->id)
            ->wherePivot('is_active', true)
            ->wherePivotIn('role', [self::ROLE_PROJECT_MANAGER])
            ->exists();
    }

    public function ownedProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'owner_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class)
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function activeProjects(): BelongsToMany
    {
        return $this->projects()->wherePivot('is_active', true);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class)->withTimestamps();
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function apiTokens(): HasMany
    {
        return $this->hasMany(ApiToken::class);
    }

    public function mobileDeviceTokens(): HasMany
    {
        return $this->hasMany(MobileDeviceToken::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class);
    }

    public function uploadedFiles(): HasMany
    {
        return $this->hasMany(ProjectFile::class, 'uploaded_by');
    }

    protected static function booted(): void
    {
        static::saving(function (User $user): void {
            $role = $user->role;

            if (! in_array($role, array_keys(self::roleOptions()), true)) {
                $role = $user->is_admin ? self::ROLE_ADMIN : self::ROLE_MEMBER;
            }

            $user->role = $role;
            $user->is_admin = $role === self::ROLE_ADMIN;
            $user->is_active = $user->is_active ?? true;
        });
    }
}

