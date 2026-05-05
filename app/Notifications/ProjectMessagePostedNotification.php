<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ProjectMessagePostedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public readonly Project $project,
        public readonly ProjectMessage $message,
        public readonly ?User $postedBy = null,
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
        $actor = $this->postedBy?->name ?? 'Un membre de l equipe';
        $excerpt = Str::limit($this->message->body, 180);

        return (new MailMessage)
            ->subject('Nouveau message interne')
            ->greeting('Bonjour,')
            ->line("{$actor} a publie un nouveau message dans le projet {$this->project->name}.")
            ->line("Message: {$excerpt}")
            ->action('Voir le projet', url('/client/projects/'.$this->project->id.'#chat'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'project_message_posted',
            'project_id' => $this->project->id,
            'project_name' => $this->project->name,
            'message_id' => $this->message->id,
            'excerpt' => Str::limit($this->message->body, 180),
            'posted_by' => $this->postedBy?->name,
            'url' => url('/client/projects/'.$this->project->id.'#chat'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => 'Nouveau message',
            'body' => 'Projet '.$this->project->name.': '.Str::limit($this->message->body, 90),
            'data' => [
                'type' => 'project_message_posted',
                'project_id' => $this->project->id,
                'url' => '/client/projects/'.$this->project->id.'#chat',
            ],
        ];
    }
}

