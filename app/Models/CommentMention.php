<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommentMention extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_comment_id',
        'user_id',
    ];

    public function taskComment(): BelongsTo
    {
        return $this->belongsTo(TaskComment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

