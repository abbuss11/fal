<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Project extends Model
{
    use HasFactory;

    public const STATUS_PLANNING = 'planning';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ON_HOLD = 'on_hold';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'owner_id',
        'name',
        'description',
        'objective',
        'status',
        'priority',
        'budget',
        'start_date',
        'due_date',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'decimal:2',
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['role', 'is_active'])
            ->withTimestamps();
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->members()->wherePivot('is_active', true);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function subtasks(): HasManyThrough
    {
        return $this->hasManyThrough(TaskSubtask::class, Task::class);
    }

    public function comments(): HasManyThrough
    {
        return $this->hasManyThrough(TaskComment::class, Task::class);
    }

    public function activityLogs(): HasManyThrough
    {
        return $this->hasManyThrough(ActivityLog::class, Task::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ProjectMessage::class)->latest();
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class)->latest();
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_PLANNING => 'Planning',
            self::STATUS_ACTIVE => 'Actif',
            self::STATUS_ON_HOLD => 'En pause',
            self::STATUS_COMPLETED => 'Termine',
            self::STATUS_CANCELLED => 'Annule',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }
}
