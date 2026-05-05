<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserMentionedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly string $contextLabel,
        public readonly string $excerpt,
        public readonly ?User $mentionedBy = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'broadcast', MobilePushChannel::class];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $actor = $this->mentionedBy?->name ?? 'Un membre de l equipe';

        return (new MailMessage)
            ->subject('Vous avez ete mentionne')
            ->greeting('Bonjour,')
            ->line("{$actor} vous a mentionne dans {$this->contextLabel}.")
            ->line("Extrait: {$this->excerpt}")
            ->action('Ouvrir le projet', url('/client/projects/'.$this->project->id))
            ->line('Merci pour votre collaboration.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_mentioned',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'context' => $this->contextLabel,
            'excerpt' => $this->excerpt,
            'mentioned_by' => $this->mentionedBy?->name,
            'url' => url('/client/projects/'.$this->project->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Nouvelle mention',
            'body' => 'Vous avez ete mentionne dans '.$this->project->name,
            'data' => [
                'type' => 'user_mentioned',
                'project_id' => $this->project->id,
                'url' => '/client/projects/'.$this->project->id,
            ],
        ];
    }
}

