<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'body',
        'mentions',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'mentions' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

