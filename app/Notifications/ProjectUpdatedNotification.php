<?php

namespace App\Notifications;

use App\Models\Project;
use App\Models\User;
use App\Notifications\Channels\MobilePushChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ProjectUpdatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Project $project,
        public string $subjectLine,
        public string $details,
        public ?User $updatedBy = null,
    ) {}

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
        $actor = $this->updatedBy?->name ?? 'un membre de l equipe';

        return (new MailMessage)
            ->subject($this->subjectLine)
            ->greeting('Bonjour,')
            ->line("Projet: {$this->project->name}")
            ->line("Mise a jour par: {$actor}")
            ->line($this->details)
            ->action('Voir le projet', url("/client/projects/{$this->project->id}"))
            ->line('Merci de votre collaboration.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'project_id' => $this->project->id,
            'subject' => $this->subjectLine,
            'details' => $this->details,
            'type' => 'project_updated',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toMobilePush(object $notifiable): array
    {
        return [
            'title' => $this->subjectLine,
            'body' => $this->details,
            'data' => [
                'type' => 'project_updated',
                'project_id' => $this->project->id,
                'url' => '/client/projects/'.$this->project->id,
            ],
        ];
    }
}
