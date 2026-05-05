<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends Model
{
    use HasFactory;

    public const STATUS_TODO = 'todo';

    public const STATUS_DOING = 'doing';

    public const STATUS_DONE = 'done';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_URGENT = 'urgent';

    protected $fillable = [
        'project_id',
        'assigned_to',
        'title',
        'description',
        'status',
        'is_in_review',
        'priority',
        'estimated_hours',
        'due_date',
        'completed_at',
        'position',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
            'is_in_review' => 'boolean',
            'estimated_hours' => 'integer',
            'position' => 'integer',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_TODO => 'To Do',
            self::STATUS_DOING => 'Doing',
            self::STATUS_DONE => 'Done',
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_URGENT => 'Urgente',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    public function subtasks(): HasMany
    {
        return $this->hasMany(TaskSubtask::class)->orderBy('position');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProjectFile::class);
    }

    public function timesheets(): HasMany
    {
        return $this->hasMany(Timesheet::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class)->latest();
    }

    public function getNormalizedStatusAttribute(): string
    {
        return match ($this->status) {
            'in_progress', 'blocked' => self::STATUS_DOING,
            default => $this->status,
        };
    }

    public function getBoardLaneAttribute(): string
    {
        if ($this->normalized_status === self::STATUS_DOING && (bool) $this->is_in_review) {
            return 'review';
        }

        return $this->normalized_status;
    }
}
