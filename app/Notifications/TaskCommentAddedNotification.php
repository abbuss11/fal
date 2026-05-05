<?php

namespace App\Notifications;

use App\Models\TaskComment;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TaskCommentAddedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public TaskComment $comment) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', MobilePushChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $authorName = $this->comment->user?->name ?? 'Un membre de l equipe';
        $taskTitle = $this->comment->task?->title ?? 'Tache';
        $excerpt = Str::limit($this->comment->body, 140);
        $projectId = $this->comment->task?->project_id
            ?? $this->comment->task()->value('project_id');

        return (new MailMessage)
            ->subject('Nouveau commentaire sur une tache')
            ->greeting('Bonjour,')
            ->line("{$authorName} a commente la tache \"{$taskTitle}\".")
            ->line("Commentaire : {$excerpt}")
            ->action('Voir dans l espace client', url("/client/projects/{$projectId}#comments"))
            ->line("Lien admin: ".url("/abba/tasks/{$this->comment->task_id}/edit"))
            ->line('Merci pour votre collaboration.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'task_id' => $this->comment->task_id,
            'comment_id' => $this->comment->id,
            'type' => 'task_comment_added',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        $projectId = $this->comment->task?->project_id
            ?? $this->comment->task()->value('project_id');

        return [
            'title' => 'Nouveau commentaire',
            'body' => Str::limit($this->comment->body, 90),
            'data' => [
                'type' => 'task_comment_added',
                'task_id' => $this->comment->task_id,
                'comment_id' => $this->comment->id,
                'project_id' => $projectId,
                'url' => '/client/projects/'.$projectId.'#comments',
            ],
        ];
    }
}
